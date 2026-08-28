#!/bin/bash
# ============================================================================
# 容器启动时自动完成初始化：等待数据库、生成 key、迁移、缓存配置
# ============================================================================
set -e

cd /var/www/html

# 若无 .env 则从示例复制
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[entrypoint] 已从 .env.example 生成 .env"
fi

# 用容器环境变量覆盖 .env 中的数据库配置（compose 注入）
set_env() {
    local key="$1" val="$2"
    if grep -q "^${key}=" .env; then
        # 使用 | 作为分隔符，避免值中出现 / 冲突
        sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
        echo "${key}=${val}" >> .env
    fi
}
set_env DB_CONNECTION "${DB_CONNECTION:-mysql}"
set_env DB_HOST       "${DB_HOST:-mysql}"
set_env DB_PORT       "${DB_PORT:-3306}"
set_env DB_DATABASE   "${DB_DATABASE:-cardshop}"
set_env DB_USERNAME   "${DB_USERNAME:-cardshop}"
set_env DB_PASSWORD   "${DB_PASSWORD:-cardshop_pass}"
echo "[entrypoint] 已同步数据库配置到 .env"

# 生成 APP_KEY（若为空）
if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --force || true
    echo "[entrypoint] 已生成 APP_KEY"
fi

# 等待 MySQL 就绪
echo "[entrypoint] 等待数据库连接..."
until php -r "
    try {
        new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'),
            getenv('DB_USERNAME'), getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Exception \$e) { exit(1); }
" 2>/dev/null; do
    echo "[entrypoint] 数据库尚未就绪，2 秒后重试..."
    sleep 2
done
echo "[entrypoint] 数据库已连接"

# 运行迁移（首次会 seed 出管理员/示例商品/卡密）
php artisan migrate --force
if [ "${RUN_SEED:-true}" = "true" ]; then
    php artisan db:seed --force || true
fi

# 生产环境缓存优化
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 修正权限
chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] 初始化完成，启动服务"
exec "$@"
