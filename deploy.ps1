<#
============================================================================
 CardShop 一键部署脚本 (Windows PowerShell)

   用法:  .\deploy.ps1              # 构建并启动
          .\deploy.ps1 down         # 停止
          .\deploy.ps1 logs         # 查看日志
          .\deploy.ps1 reset        # 停止并清空数据库

 依赖: Docker Desktop（含 Docker Compose v2）
============================================================================
#>
param(
    [string]$Action = "up"
)

$ErrorActionPreference = "Stop"
Set-Location -LiteralPath $PSScriptRoot

function Info($m)  { Write-Host "[+] $m" -ForegroundColor Green }
function Warn($m)  { Write-Host "[!] $m" -ForegroundColor Yellow }
function Fail($m)  { Write-Host "[x] $m" -ForegroundColor Red }

# --- 检测 docker ---
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Fail "未检测到 Docker，请先安装 Docker Desktop: https://www.docker.com/products/docker-desktop/"
    exit 1
}

# 检测 compose v2
docker compose version *> $null
if ($LASTEXITCODE -ne 0) {
    Fail "未检测到 Docker Compose v2，请升级 Docker Desktop"
    exit 1
}

if (-not $env:APP_PORT) { $env:APP_PORT = "8080" }
$port = $env:APP_PORT

switch ($Action) {
    "down" {
        Info "停止容器..."
        docker compose down
    }
    "reset" {
        Warn "停止容器并清空数据卷（数据库将被清空）..."
        docker compose down -v
        Info "已清理"
    }
    "logs" {
        docker compose logs -f
    }
    { $_ -in @("up", "") } {
        Info "构建并启动容器（首次会稍慢）..."
        docker compose up -d --build
        Info "等待服务就绪..."
        Start-Sleep -Seconds 5
        Write-Host ""
        Info "部署完成！"
        Write-Host "    前台:  http://localhost:$port"
        Write-Host "    后台:  http://localhost:$port/admin"
        Write-Host "    默认管理员: admin@example.com / admin123456"
        Write-Host ""
        Write-Host "    查看日志: .\deploy.ps1 logs"
        Write-Host "    停止服务: .\deploy.ps1 down"
    }
    default {
        Fail "未知命令: $Action"
        Write-Host "用法: .\deploy.ps1 [up|down|logs|reset]"
        exit 1
    }
}
