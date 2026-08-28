#!/usr/bin/env bash
# ============================================================================
# CardShop 宿主机安装脚本 (Linux / macOS) —— 不使用 Docker
#
# 作用：在已装 PHP 8.1+ / Composer / MySQL 的机器上初始化项目。
#   - 安装 composer 依赖
#   - 复制 .env、生成 APP_KEY
#   - 运行迁移与 seed
#
# 用法：./setup.sh
# ============================================================================
set -euo pipefail
cd "$(dirname "$0")"

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[+]${NC} $1"; }
warn()  { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[x]${NC} $1"; }

# --- 依赖检测 ---
command -v php >/dev/null 2>&1      || { error "未找到 PHP，请安装 PHP 8.1+"; exit 1; }
command -v composer >/dev/null 2>&1 || { error "未找到 Composer: https://getcomposer.org/"; exit 1; }

PHP_VER=$(php -r 'echo PHP_VERSION;')
info "检测到 PHP $PHP_VER"

# --- 安装依赖 ---
info "安装 Composer 依赖..."
composer install --no-interaction --prefer-dist

# --- 环境文件 ---
if [ ! -f .env ]; then
    cp .env.example .env
    info "已生成 .env（请按需修改数据库配置）"
fi

if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate
    info "已生成 APP_KEY"
fi

# --- 数据库 ---
warn "请确认 .env 中的数据库配置正确，且数据库已创建 (CREATE DATABASE cardshop;)"
read -r -p "现在运行数据库迁移 + 初始化数据？(y/N) " ans
if [[ "$ans" =~ ^[Yy]$ ]]; then
    php artisan migrate --seed
    info "数据库初始化完成"
else
    warn "已跳过。稍后可手动运行: php artisan migrate --seed"
fi

echo
info "安装完成！启动开发服务器："
echo "    php artisan serve"
echo "    前台 http://localhost:8000  后台 http://localhost:8000/admin"
echo "    默认管理员: admin@example.com / admin123456"
