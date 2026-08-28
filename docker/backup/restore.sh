#!/bin/sh
# ============================================================================
# 数据库恢复脚本（在 backup 容器内运行）
#
# 用法（在宿主机）：
#   docker compose exec backup restore.sh /backups/cardshop_20260101_030000.sql.gz
#
# 或列出可用备份：
#   docker compose exec backup ls -lh /backups
# ============================================================================
set -eu

FILE="${1:-}"

if [ -z "$FILE" ]; then
    echo "用法: restore.sh <备份文件路径>"
    echo "可用备份："
    ls -lh /backups 2>/dev/null | grep ".sql.gz" || echo "  （无）"
    exit 1
fi

if [ ! -f "$FILE" ]; then
    echo "!! 文件不存在: $FILE"
    exit 1
fi

echo "!! 警告：即将用 $FILE 覆盖数据库 ${DB_DATABASE}"
echo "!! 这会覆盖现有数据。5 秒后开始（Ctrl+C 取消）..."
sleep 5

echo "[$(date '+%F %T')] 开始恢复 ..."
gunzip -c "$FILE" | mysql \
    --host="${DB_HOST}" \
    --port="${DB_PORT:-3306}" \
    --user="${DB_USERNAME}" \
    --password="${DB_PASSWORD}" \
    "${DB_DATABASE}"

echo "[$(date '+%F %T')] 恢复完成。"
