# CardShop — 授权校验 + 发卡系统

基于 **Laravel 10 + MySQL** 的一体化系统，包含三大模块：

1. **登录 + 有效期校验 API**（供你的 C/C++ 程序调用，含 HMAC 签名防篡改、时间戳防重放、机器码绑定）
2. **卡密系统**（生成 / 激活 / 续期）
3. **发卡网站前台**（商品展示 / 下单 / 自动发货）
4. **管理后台**（账号 / 卡密 / 商品 / 订单）

---

## 目录结构

```
cardshop/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/LicenseController.php      # 登录/心跳/兑换 API
│   │   ├── Shop/ShopController.php        # 发卡前台
│   │   └── Admin/*                        # 后台 CRUD
│   ├── Http/Middleware/
│   │   └── VerifyLicenseSignature.php     # 请求签名+时间戳校验
│   ├── Models/                            # AppUser/Product/CardKey/Order/...
│   └── Services/
│       ├── SignatureService.php           # HMAC 签名核心
│       ├── LicenseService.php             # 登录/卡密激活逻辑
│       └── OrderService.php               # 下单/发货逻辑
├── database/migrations/                   # 建表
├── database/seeders/DatabaseSeeder.php    # 初始化管理员+示例商品+卡密
├── resources/views/                       # 前台/后台页面
├── routes/{web,api}.php
├── client-example/license_client.cpp      # C++ 客户端示例
└── config/license.php                     # 签名密钥配置
```

---

## 快速开始

> 需要 PHP 8.1+、Composer、MySQL 5.7+/8.0。

```bash
cd cardshop

# 1. 安装依赖
composer install

# 2. 配置环境
cp .env.example .env
php artisan key:generate
#   编辑 .env：数据库连接、LICENSE_HMAC_SECRET（改成长随机串）

# 3. 建库（先在 MySQL 中 CREATE DATABASE cardshop;）
php artisan migrate --seed

# 4. 启动
php artisan serve
```

- 前台：http://localhost:8000
- 后台：http://localhost:8000/admin
  默认管理员：`.env` 中的 `ADMIN_EMAIL` / `ADMIN_PASSWORD`（默认 admin@example.com / admin123456）

---

## 一键部署（跨平台，推荐）

无需在本机安装 PHP/MySQL，只要有 **Docker**，一条命令即可拉起 PHP + MySQL + Nginx 全套环境。

### Windows

```powershell
.\deploy.ps1            # 构建并启动
.\deploy.ps1 logs       # 查看日志
.\deploy.ps1 down       # 停止
.\deploy.ps1 reset      # 停止并清空数据库
```

### Linux / macOS

```bash
chmod +x deploy.sh
./deploy.sh             # 构建并启动
./deploy.sh logs        # 查看日志
./deploy.sh down        # 停止
./deploy.sh reset       # 停止并清空数据库
```

部署完成后访问 `http://localhost:8080`（后台 `/admin`）。
可用环境变量自定义：`APP_PORT`、`DB_DATABASE`、`DB_USERNAME`、`DB_PASSWORD`。

> 也可直接用原生 compose：`docker compose up -d --build`

### 不用 Docker（宿主机直装）

已装 PHP 8.1+ / Composer / MySQL 时：

```bash
./setup.sh        # Linux/macOS
```
```powershell
.\setup.ps1       # Windows
```

脚本会自动装依赖、生成 `.env` 与 `APP_KEY`、运行迁移与初始化数据。

---

## License API

所有接口都要求携带签名字段：`timestamp`、`nonce`、`sign`。

**签名算法**：
1. 取所有业务参数（不含 `sign`），按 key 升序拼成 `k1=v1&k2=v2&...`
2. `sign = HMAC_SHA256(canonical, LICENSE_HMAC_SECRET)`（十六进制小写）

### POST `/api/v1/login`
| 参数 | 说明 |
|---|---|
| username | 账号 |
| password | 密码 |
| machine_code | 机器码（可选，用于绑定设备）|
| timestamp / nonce / sign | 签名字段 |

成功返回：
```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "username": "user1",
    "expires_at": 1735689600,
    "expires_at_human": "2025-01-01 00:00:00",
    "remaining_seconds": 2592000,
    "server_time": 1704067200
  },
  "nonce": "…",
  "sign": "…"    // 服务器响应签名，客户端应校验
}
```

失败返回 `code != 0`，常见：
- 1001 账号或密码错误
- 1002 账号封禁
- 1003 账号过期/未激活
- 1004 设备不匹配
- 4001 请求过期（时间戳超差）
- 4002 请求签名错误

### POST `/api/v1/heartbeat`
运行期周期性复验有效期。参数：`username`、`machine_code?` + 签名字段。

### POST `/api/v1/redeem`
卡密激活/续期。参数：`code`、`username`、`password?`、`register?` + 签名字段。

---

## C/C++ 客户端

见 `client-example/license_client.cpp`，依赖 libcurl + OpenSSL：

```bash
g++ license_client.cpp -o license_client -lcurl -lcrypto
```

客户端会：
- 对请求参数做 HMAC 签名并附带时间戳/nonce；
- 校验服务器返回的 `sign`（对 `code`+`server_time`+`nonce` 签名），
  防止本地 hosts / 中间人伪造返回绕过验证。

---

## 安全须知

- **HMAC 密钥内嵌在客户端二进制中始终有被逆向的风险**，本方案用于*提高破解成本*，
  并非绝对防线。要更强保护可叠加：
  - HTTPS + **证书绑定**（curl `CURLOPT_PINNEDPUBLICKEY`）
  - 服务端风控（IP/频率/异常登录检测，已内置 login_logs）
  - 关键逻辑上云（把核心计算放到服务端，客户端只当壳）
- 生产环境务必：
  - 关闭 `APP_DEBUG`
  - 使用强随机 `APP_KEY` 与 `LICENSE_HMAC_SECRET`
  - 支付回调**必须验签**后再发货（示例中的 mockPaid 仅供演示）
  - 后台加上 HTTPS 与访问白名单

---

## 生产化 TODO（按需扩展）

- 对接真实支付：支付宝/微信/易支付，回调路由在 `VerifyCsrfToken::$except` 放行并验签
- 队列化发卡/发邮件
- 后台操作日志、双因子登录
- API 增加 IP 限速（已配 `throttle:api` 120/min）
