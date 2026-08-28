#!/usr/bin/env bash
# ============================================================================
# CardShop 一键部署引导脚本（用于全新 Linux 服务器）
#
# 使用方法（在新服务器上执行一行命令）：
#
#   curl -fsSL https://raw.githubusercontent.com/ziyisj/cardshop/main/bootstrap.sh | bash
#
# 该脚本会自动：
#   1. 检测系统并安装 Docker + Docker Compose（若未安装）
#   2. 克隆（或更新）本仓库到 /opt/cardshop
#   3. 生成 .env 并写入随机密钥
#   4. docker compose 构建并启动 PHP + MySQL + Nginx
#
# 可选环境变量：
#   APP_PORT       对外端口（默认 8080）
#   INSTALL_DIR    安装目录（默认 /opt/cardshop）
#   REPO_URL       仓库地址（默认本仓库）
# ============================================================================
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/ziyisj/cardshop.git}"
INSTALL_DIR="${INSTALL_DIR:-/opt/cardshop}"
APP_PORT="${APP_PORT:-8080}"
BRANCH="${BRANCH:-main}"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[+]${NC} $1"; }
warn()  { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[x]${NC} $1"; }

# ---------- 0. 需要 root / sudo ----------
SUDO=""
if [ "$(id -u)" -ne 0 ]; then
    if command -v sudo >/dev/null 2>&1; then
        SUDO="sudo"
    else
        error "需要 root 权限，且系统无 sudo。请用 root 用户运行。"
        exit 1
    fi
fi

# ---------- 1. 安装基础工具 ----------
install_pkg() {
    if command -v apt-get >/dev/null 2>&1; then
        $SUDO apt-get update -y
        $SUDO apt-get install -y "$@"
    elif command -v dnf >/dev/null 2>&1; then
        $SUDO dnf install -y "$@"
    elif command -v yum >/dev/null 2>&1; then
        $SUDO yum install -y "$@"
    elif command -v apk >/dev/null 2>&1; then
        $SUDO apk add --no-cache "$@"
    else
        error "未识别的包管理器，请手动安装：$*"
        exit 1
    fi
}

command -v curl >/dev/null 2>&1 || install_pkg curl
command -v git  >/dev/null 2>&1 || install_pkg git

# ---------- 2. 安装 Docker ----------
if ! command -v docker >/dev/null 2>&1; then
    info "未检测到 Docker，正在安装..."
    curl -fsSL https://get.docker.com | $SUDO sh
    $SUDO systemctl enable docker >/dev/null 2>&1 || true
    $SUDO systemctl start docker  >/dev/null 2>&1 || true
    info "Docker 安装完成"
else
    info "Docker 已安装：$(docker --version)"
fi

# 确认 Docker Compose 可用（v2 插件形式）
if docker compose version >/dev/null 2>&1; then
    DC="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    DC="docker-compose"
else
    warn "未找到 Docker Compose 插件，尝试安装..."
    install_pkg docker-compose-plugin || true
    if docker compose version >/dev/null 2>&1; then
        DC="docker compose"
    else
        error "Docker Compose 不可用，请手动安装后重试"
        exit 1
    fi
fi
info "使用：$DC"

# ---------- 3. 克隆 / 更新代码 ----------
if [ -d "$INSTALL_DIR/.git" ]; then
    info "检测到已有安装，拉取最新代码..."
    $SUDO git -C "$INSTALL_DIR" fetch --all
    $SUDO git -C "$INSTALL_DIR" reset --hard "origin/$BRANCH"
else
    info "克隆仓库到 $INSTALL_DIR ..."
    $SUDO mkdir -p "$INSTALL_DIR"
    $SUDO git clone --branch "$BRANCH" "$REPO_URL" "$INSTALL_DIR"
fi

cd "$INSTALL_DIR"

# ---------- 4. 生成 .env（含随机密钥）----------
rand() { head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 40; }

if [ ! -f .env ]; then
    $SUDO cp .env.example .env
    # 写入随机的授权签名密钥与数据库密码
    HMAC="$(rand)"; SIGN="$(rand)"; DBPASS="$(rand)"
    $SUDO sed -i "s|^LICENSE_HMAC_SECRET=.*|LICENSE_HMAC_SECRET=${HMAC}|" .env
    $SUDO sed -i "s|^LICENSE_SIGN_SECRET=.*|LICENSE_SIGN_SECRET=${SIGN}|" .env
    info "已生成 .env（含随机 LICENSE 密钥）"
    warn "数据库密码将通过 compose 传入，如需自定义可编辑 $INSTALL_DIR/.env"
fi

# ---------- 5. 启动 ----------
info "构建并启动容器（首次较慢，请耐心等待）..."
export APP_PORT
$SUDO -E env APP_PORT="$APP_PORT" $DC up -d --build

# ---------- 6. 输出结果 ----------
IP="$(curl -fsSL https://api.ipify.org 2>/dev/null || echo 'your-server-ip')"
echo
info "部署完成！"
echo "    前台:  http://${IP}:${APP_PORT}"
echo "    后台:  http://${IP}:${APP_PORT}/admin"
echo "    默认管理员: admin@example.com / admin123456（请登录后立即修改）"
echo
echo "    安装目录: $INSTALL_DIR"
echo "    查看日志: cd $INSTALL_DIR && $DC logs -f"
echo "    停止服务: cd $INSTALL_DIR && $DC down"
echo "    更新重部署: curl -fsSL ${REPO_URL%.git}/raw/${BRANCH}/bootstrap.sh | bash"
echo
warn "生产环境请务必：修改默认管理员密码、配置 HTTPS、检查防火墙放行端口 ${APP_PORT}"
