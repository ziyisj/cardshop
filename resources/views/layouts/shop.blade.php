<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '发卡商城')</title>
    <style>
        :root{--bg:#0f172a;--card:#1e293b;--accent:#38bdf8;--text:#e2e8f0;--muted:#94a3b8}
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,"Microsoft YaHei",sans-serif;background:var(--bg);color:var(--text)}
        header{background:#111827;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
        header a{color:var(--accent);text-decoration:none;font-weight:600}
        .container{max-width:960px;margin:24px auto;padding:0 16px}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
        .card{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 4px 12px rgba(0,0,0,.3)}
        .card h3{margin:0 0 8px}
        .price{color:#f59e0b;font-size:22px;font-weight:700}
        .muted{color:var(--muted);font-size:14px}
        .btn{display:inline-block;background:var(--accent);color:#04283a;padding:10px 18px;border:none;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none}
        .btn:hover{opacity:.9}
        input,select,textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #334155;background:#0b1220;color:var(--text);margin:6px 0}
        label{font-size:14px;color:var(--muted)}
        .alert{background:#7f1d1d;padding:10px 14px;border-radius:8px;margin:10px 0}
        .ok{background:#14532d}
        code{background:#0b1220;padding:2px 6px;border-radius:4px}
        .codes{background:#0b1220;padding:14px;border-radius:8px;white-space:pre-wrap;font-family:monospace;font-size:15px;line-height:1.8}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid #334155;text-align:left;font-size:14px}
    </style>
</head>
<body>
<header>
    <a href="{{ route('shop.index') }}">🛒 发卡商城</a>
    <a href="{{ route('shop.query') }}">订单查询</a>
</header>
<div class="container">
    @if($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif
    @yield('content')
</div>
</body>
</html>
