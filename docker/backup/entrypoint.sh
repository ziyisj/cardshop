#!/bin/sh
# ============================================================================
# backup 容器入口：安装 mysql-client（+可选 aws cli），配置并启动 cron
#
# 环境变量：
#   BACKUP_CRON        cron 表达式（默认每天 03:00 -> "0 3 * * *"）
#   BACKUP_ON_START    容器启动时是否立即跑一次备份（默认 false）
# ============================================================================
set -eu

CRON_EXPR="${BACKUP_CRON:-0 3 * * *}"

echo "[backup] 安装依赖 ..."
apk add --no-cache mysql-client gzip findutils tzdata >/dev/null 2>&1 || true

# 若配置了 S3，则安装 aws cli
if [ -n "${S3_BUCKET:-}" ]; then
    echo "[backup] 检测到 S3 配置，安装 aws-cli ..."
    apk add --no-cache aws-cli >/dev/null 2>&1 || echo "[backup] !! aws-cli 安装失败，S3 上传将被跳过"
fi

# 把需要的环境变量写入文件，供 cron 任务加载（cron 不继承容器 env）
ENV_FILE="/etc/backup.env"
{
    echo "export DB_HOST='${DB_HOST}'"
    echo "export DB_PORT='${DB_PORT:-3306}'"
    echo "export DB_DATABASE='${DB_DATABASE}'"
    echo "export DB_USERNAME='${DB_USERNAME}'"
    echo "export DB_PASSWORD='${DB_PASSWORD}'"
    echo "export BACKUP_KEEP_DAYS='${BACKUP_KEEP_DAYS:-7}'"
    echo "export S3_BUCKET='${S3_BUCKET:-}'"
    echo "export S3_ENDPOINT='${S3_ENDPOINT:-}'"
    echo "export S3_ACCESS_KEY='${S3_ACCESS_KEY:-}'"
    echo "export S3_SECRET_KEY='${S3_SECRET_KEY:-}'"
    echo "export S3_REGION='${S3_REGION:-us-east-1}'"
} > "$ENV_FILE"
chmod 600 "$ENV_FILE"

# cron 任务：加载环境变量后执行备份脚本，日志输出到容器 stdout
CRON_JOB="${CRON_EXPR} . ${ENV_FILE}; /bin/sh /usr/local/bin/backup.sh >> /proc/1/fd/1 2>&1"
echo "$CRON_JOB" > /etc/crontabs/root
echo "[backup] 已配置定时任务：${CRON_EXPR}"

# 可选：启动时立即备份一次
if [ "${BACKUP_ON_START:-false}" = "true" ]; then
    echo "[backup] BACKUP_ON_START=true，立即执行一次备份 ..."
    . "$ENV_FILE"; /bin/sh /usr/local/bin/backup.sh || echo "[backup] !! 首次备份失败"
fi

echo "[backup] 启动 crond（前台）..."
exec crond -f -l 8
