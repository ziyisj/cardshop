<#
============================================================================
 CardShop 宿主机安装脚本 (Windows PowerShell) —— 不使用 Docker

 作用: 在已装 PHP 8.1+ / Composer / MySQL 的机器上初始化项目。

 用法: .\setup.ps1
============================================================================
#>
$ErrorActionPreference = "Stop"
Set-Location -LiteralPath $PSScriptRoot

function Info($m) { Write-Host "[+] $m" -ForegroundColor Green }
function Warn($m) { Write-Host "[!] $m" -ForegroundColor Yellow }
function Fail($m) { Write-Host "[x] $m" -ForegroundColor Red }

# --- 依赖检测 ---
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Fail "未找到 PHP，请安装 PHP 8.1+ 并加入 PATH"
    exit 1
}
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    Fail "未找到 Composer: https://getcomposer.org/"
    exit 1
}

$phpVer = (php -r "echo PHP_VERSION;")
Info "检测到 PHP $phpVer"

# --- 安装依赖 ---
Info "安装 Composer 依赖..."
composer install --no-interaction --prefer-dist

# --- 环境文件 ---
if (-not (Test-Path -LiteralPath ".env")) {
    Copy-Item -LiteralPath ".env.example" -Destination ".env"
    Info "已生成 .env（请按需修改数据库配置）"
}

$envContent = Get-Content -LiteralPath ".env" -Raw
if ($envContent -notmatch "(?m)^APP_KEY=base64") {
    php artisan key:generate
    Info "已生成 APP_KEY"
}

# --- 数据库 ---
Warn "请确认 .env 中的数据库配置正确，且数据库已创建 (CREATE DATABASE cardshop;)"
$ans = Read-Host "现在运行数据库迁移 + 初始化数据？(y/N)"
if ($ans -match "^[Yy]$") {
    php artisan migrate --seed
    Info "数据库初始化完成"
} else {
    Warn "已跳过。稍后可手动运行: php artisan migrate --seed"
}

Write-Host ""
Info "安装完成！启动开发服务器："
Write-Host "    php artisan serve"
Write-Host "    前台 http://localhost:8000  后台 http://localhost:8000/admin"
Write-Host "    默认管理员: admin@example.com / admin123456"
