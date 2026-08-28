<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>后台登录</title>
    <style>
        body{margin:0;font-family:system-ui,"Microsoft YaHei",sans-serif;background:#0f172a;display:flex;align-items:center;justify-content:center;height:100vh}
        .box{background:#1e293b;padding:32px;border-radius:12px;width:320px;color:#e2e8f0}
        h1{text-align:center;margin:0 0 20px;font-size:22px}
        input{width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #334155;background:#0b1220;color:#e2e8f0}
        button{width:100%;padding:11px;background:#0ea5e9;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;margin-top:10px}
        .err{background:#7f1d1d;padding:8px;border-radius:6px;font-size:14px;margin-bottom:8px}
        label{font-size:13px;color:#94a3b8}
    </style>
</head>
<body>
<form class="box" method="POST" action="{{ route('admin.login') }}">
    @csrf
    <h1>管理后台登录</h1>
    @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
    <label>邮箱</label>
    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
    <label>密码</label>
    <input type="password" name="password" required>
    <label><input type="checkbox" name="remember" style="width:auto"> 记住我</label>
    <button type="submit">登录</button>
</form>
</body>
</html>
