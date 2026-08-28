// ============================================================================
// License 校验客户端示例 (C++)
//
// 依赖：libcurl、OpenSSL
//   Windows (vcpkg): vcpkg install curl openssl
//   Linux:           apt install libcurl4-openssl-dev libssl-dev
//
// 编译：
//   g++ license_client.cpp -o license_client -lcurl -lcrypto
//
// 说明：
//   - 与服务器共享 HMAC 密钥（须与 .env 的 LICENSE_HMAC_SECRET 一致）。
//   - 请求参数按 key 升序拼接后做 HMAC-SHA256，附加 timestamp/nonce 防重放。
//   - 校验服务器返回的 sign（对 code/server_time/nonce 三个字段签名），
//     防止本地 hosts / 中间人伪造返回结果绕过验证。
//   - 密钥内嵌在二进制中始终有被逆向的风险，建议叠加 TLS 证书绑定等手段。
// ============================================================================

#include <curl/curl.h>
#include <openssl/hmac.h>
#include <openssl/rand.h>

#include <algorithm>
#include <chrono>
#include <cstdio>
#include <cstring>
#include <iostream>
#include <map>
#include <sstream>
#include <string>
#include <vector>

// ==== 配置：改成你自己的 ====
static const std::string API_BASE   = "http://localhost:8000/api/v1";
static const std::string HMAC_SECRET = "change_this_to_a_long_random_secret_key_2026"; // 与服务器一致

// ---------- 工具函数 ----------

static std::string toHex(const unsigned char* data, size_t len) {
    static const char* hex = "0123456789abcdef";
    std::string out;
    out.reserve(len * 2);
    for (size_t i = 0; i < len; ++i) {
        out.push_back(hex[data[i] >> 4]);
        out.push_back(hex[data[i] & 0xF]);
    }
    return out;
}

static std::string hmacSha256(const std::string& key, const std::string& msg) {
    unsigned char result[EVP_MAX_MD_SIZE];
    unsigned int len = 0;
    HMAC(EVP_sha256(),
         key.data(), (int)key.size(),
         (const unsigned char*)msg.data(), msg.size(),
         result, &len);
    return toHex(result, len);
}

static std::string randomNonce(size_t bytes = 8) {
    std::vector<unsigned char> buf(bytes);
    RAND_bytes(buf.data(), (int)bytes);
    return toHex(buf.data(), bytes);
}

static long nowUnix() {
    return (long)std::chrono::duration_cast<std::chrono::seconds>(
        std::chrono::system_clock::now().time_since_epoch()).count();
}

// 规范化：按 key 升序拼 key=value&...（跳过 sign）
static std::string canonical(const std::map<std::string, std::string>& params) {
    std::string s;
    bool first = true;
    for (const auto& kv : params) {           // std::map 已按 key 升序
        if (kv.first == "sign") continue;
        if (!first) s += "&";
        s += kv.first + "=" + kv.second;
        first = false;
    }
    return s;
}

static std::string signParams(std::map<std::string, std::string>& params) {
    return hmacSha256(HMAC_SECRET, canonical(params));
}

// ---------- HTTP ----------

static size_t writeCb(void* ptr, size_t size, size_t nmemb, void* userdata) {
    ((std::string*)userdata)->append((char*)ptr, size * nmemb);
    return size * nmemb;
}

static std::string urlEncode(CURL* curl, const std::string& s) {
    char* enc = curl_easy_escape(curl, s.c_str(), (int)s.size());
    std::string out = enc ? enc : "";
    if (enc) curl_free(enc);
    return out;
}

// 发送 POST 表单请求，返回原始响应体
static std::string httpPost(const std::string& url,
                            const std::map<std::string, std::string>& params) {
    CURL* curl = curl_easy_init();
    std::string response;
    if (!curl) return response;

    std::string body;
    bool first = true;
    for (const auto& kv : params) {
        if (!first) body += "&";
        body += urlEncode(curl, kv.first) + "=" + urlEncode(curl, kv.second);
        first = false;
    }

    curl_easy_setopt(curl, CURLOPT_URL, url.c_str());
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS, body.c_str());
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, writeCb);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &response);
    curl_easy_setopt(curl, CURLOPT_TIMEOUT, 15L);
    // 生产环境务必开启并配置证书验证；如需证书绑定可用 CURLOPT_PINNEDPUBLICKEY
    // curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 1L);

    curl_easy_perform(curl);
    curl_easy_cleanup(curl);
    return response;
}

// ---------- 极简 JSON 取值（仅用于示例，生产请用真正的 JSON 库）----------

static std::string jsonGetString(const std::string& json, const std::string& key) {
    std::string pat = "\"" + key + "\"";
    size_t p = json.find(pat);
    if (p == std::string::npos) return "";
    p = json.find(':', p);
    if (p == std::string::npos) return "";
    p++;
    while (p < json.size() && (json[p] == ' ' || json[p] == '\t')) p++;
    if (p < json.size() && json[p] == '"') {           // 字符串值
        size_t end = json.find('"', p + 1);
        return json.substr(p + 1, end - p - 1);
    }
    size_t end = json.find_first_of(",}", p);           // 数字/布尔
    std::string v = json.substr(p, end - p);
    // trim
    while (!v.empty() && (v.back() == ' ' || v.back() == '\n')) v.pop_back();
    return v;
}

// ---------- 业务：登录并校验有效期 ----------

struct LoginResult {
    bool ok = false;
    long code = -1;
    std::string message;
    long expiresAt = 0;
    long remaining = 0;
};

static LoginResult login(const std::string& username,
                         const std::string& password,
                         const std::string& machineCode) {
    std::map<std::string, std::string> params;
    params["username"]     = username;
    params["password"]     = password;
    params["machine_code"] = machineCode;
    params["timestamp"]    = std::to_string(nowUnix());
    params["nonce"]        = randomNonce();
    params["sign"]         = signParams(params);

    std::string resp = httpPost(API_BASE + "/login", params);

    LoginResult r;
    if (resp.empty()) {
        r.message = "网络错误或无响应";
        return r;
    }

    r.code    = std::stol(jsonGetString(resp, "code").empty() ? "-1" : jsonGetString(resp, "code"));
    r.message = jsonGetString(resp, "message");

    // ---- 校验服务器响应签名，防伪造 ----
    std::string serverTime = jsonGetString(resp, "server_time");
    std::string nonce      = jsonGetString(resp, "nonce");
    std::string sign       = jsonGetString(resp, "sign");

    std::map<std::string, std::string> respParams;
    respParams["code"]        = std::to_string(r.code);
    respParams["server_time"] = serverTime.empty() ? "0" : serverTime;
    respParams["nonce"]       = nonce;
    // 注意：响应签名密钥若与请求不同，请改用 RESPONSE_SECRET
    std::string expect = hmacSha256(HMAC_SECRET, canonical(respParams));

    if (sign.empty() || expect != sign) {
        r.ok = false;
        r.message = "响应签名校验失败（可能被篡改）";
        return r;
    }

    if (r.code == 0) {
        r.ok = true;
        std::string exp = jsonGetString(resp, "expires_at");
        std::string rem = jsonGetString(resp, "remaining_seconds");
        r.expiresAt = exp.empty() ? 0 : std::stol(exp);
        r.remaining = rem.empty() ? 0 : std::stol(rem);
    }
    return r;
}

int main() {
    curl_global_init(CURL_GLOBAL_DEFAULT);

    // machine_code 可用 CPU序列号/主板UUID/网卡MAC 等生成，这里用占位
    std::string machineCode = "DEMO-MACHINE-0001";

    std::string user, pass;
    std::printf("用户名: ");
    std::getline(std::cin, user);
    std::printf("密码: ");
    std::getline(std::cin, pass);

    LoginResult r = login(user, pass, machineCode);

    if (r.ok) {
        std::printf("\n[+] 登录成功！剩余 %ld 秒（约 %ld 天）\n",
                    r.remaining, r.remaining / 86400);
        std::printf("    到期时间戳: %ld\n", r.expiresAt);
        // ==> 此处放行程序主逻辑
    } else {
        std::printf("\n[-] 验证失败 (code=%ld): %s\n", r.code, r.message.c_str());
        // ==> 退出程序
    }

    curl_global_cleanup();
    return r.ok ? 0 : 1;
}
