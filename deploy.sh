#!/usr/bin/env bash
# ============================================================================
# CardShop 一键部署脚本 (Linux / macOS)
#
#   用法：  ./deploy.sh
#           ./deploy.sh down       # 停止
#           ./deploy.sh logs       # 查看日志
#           ./deploy.sh reset      # 停止并清空数据库
#
# 依赖：Docker + Docker Compose（脚本会自动检测）
# ============================================================================
set -euo pipefail

cd "$(dirname "$0")"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[+]${NC} $1"; }
warn()  { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[x]${NC} $1"; }

# --- 检测 docker ---
if ! command -v docker >/dev/null 2>&1; then
    error "未检测到 Docker，请先安装：https://docs.docker.com/get-docker/"
    exit 1
fi

# 兼容 docker compose (v2) 与 docker-compose (v1)
if docker compose version >/dev/null 2>&1; then
    DC="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    DC="docker-compose"
else
    error "未检测到 Docker Compose，请升级 Docker 或安装 docker-compose"
    exit 1
fi

APP_PORT="${APP_PORT:-8080}"

case "${1:-up}" in
    down)
        info "停止容器..."
        $DC down
        ;;
    reset)
        warn "停止容器并清空数据卷（数据库将被清空）..."
        $DC down -v
        info "已清理"
        ;;
    logs)
        $DC logs -f
        ;;
    up|"")
        info "构建并启动容器（首次会稍慢）..."
        APP_PORT="$APP_PORT" $DC up -d --build
        info "等待服务就绪..."
        sleep 5
        echo
        info "部署完成！"
        echo "    前台:  http://localhost:${APP_PORT}"
        echo "    后台:  http://localhost:${APP_PORT}/admin"
        echo "    默认管理员: admin@example.com / admin123456"
        echo
        echo "    查看日志: ./deploy.sh logs"
        echo "    停止服务: ./deploy.sh down"
        ;;
    *)
        error "未知命令: $1"
        echo "用法: ./deploy.sh [up|down|logs|reset]"
        exit 1
        ;;
esac
