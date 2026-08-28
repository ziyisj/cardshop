# ============================================================================
# CardShop 生产镜像：PHP 8.2-FPM + 常用扩展
# ============================================================================
FROM php:8.2-fpm-alpine

# 安装系统依赖与 PHP 扩展
RUN apk add --no-cache \
        bash \
        git \
        curl \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
    && apk del $PHPIZE_DEPS

# 安装 Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 先拷贝依赖清单，利用 Docker 层缓存
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist || true

# 拷贝项目代码
COPY . .

# 生成 autoload、设置权限
RUN composer dump-autoload --optimize --no-dev || true \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# 容器启动脚本
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
