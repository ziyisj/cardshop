#!/bin/sh
# ============================================================================
# 数据库自动备份脚本（在 backup 容器内运行）
#
#   - mysqldump 导出整库
#   - gzip 压缩
#   - 保留最近 N 天（BACKUP_KEEP_DAYS），自动清理更旧的
#   - 可选：上传到 S3 兼容对象存储（配置 S3_BUCKET 等变量时启用）
#
# 环境变量（由 compose 注入）：
#   DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
#   BACKUP_KEEP_DAYS   保留天数（默认 7）
#   S3_BUCKET S3_ENDPOINT S3_ACCESS_KEY S3_SECRET_KEY S3_REGION  可选
# ============================================================================
set -eu

BACKUP_DIR="/backups"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-7}"
TS="$(date +%Y%m%d_%H%M%S)"
FILE="${BACKUP_DIR}/${DB_DATABASE}_${TS}.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "[$(date '+%F %T')] 开始备份数据库 ${DB_DATABASE} ..."

# 导出并压缩（--single-transaction 保证 InnoDB 一致性且不锁表）
mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT:-3306}" \
    --user="${DB_USERNAME}" \
    --password="${DB_PASSWORD}" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    "${DB_DATABASE}" | gzip -9 > "$FILE"

SIZE="$(du -h "$FILE" | cut -f1)"
echo "[$(date '+%F %T')] 备份完成：$FILE (${SIZE})"

# ---------- 可选：上传到 S3 兼容存储 ----------
if [ -n "${S3_BUCKET:-}" ]; then
    if command -v aws >/dev/null 2>&1; then
        echo "[$(date '+%F %T')] 上传到 S3：s3://${S3_BUCKET}/ ..."
        EXTRA=""
        [ -n "${S3_ENDPOINT:-}" ] && EXTRA="--endpoint-url ${S3_ENDPOINT}"
        AWS_ACCESS_KEY_ID="${S3_ACCESS_KEY}" \
        AWS_SECRET_ACCESS_KEY="${S3_SECRET_KEY}" \
        AWS_DEFAULT_REGION="${S3_REGION:-us-east-1}" \
            aws $EXTRA s3 cp "$FILE" "s3://${S3_BUCKET}/$(basename "$FILE")" \
            && echo "[$(date '+%F %T')] S3 上传成功" \
            || echo "[$(date '+%F %T')] !! S3 上传失败（本地备份已保留）"
    else
        echo "[$(date '+%F %T')] !! 配置了 S3 但容器内无 aws cli，跳过上传"
    fi
fi

# ---------- 清理过期本地备份 ----------
echo "[$(date '+%F %T')] 清理 ${KEEP_DAYS} 天前的旧备份 ..."
find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -type f -mtime "+${KEEP_DAYS}" -print -delete || true

echo "[$(date '+%F %T')] 当前备份列表："
ls -lh "$BACKUP_DIR" | grep "${DB_DATABASE}_" || echo "  （无）"

echo "[$(date '+%F %T')] 全部完成。"
