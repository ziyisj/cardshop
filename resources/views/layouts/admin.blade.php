<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理后台') · CardShop Admin</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,"Microsoft YaHei",sans-serif;background:#f1f5f9;color:#1e293b;display:flex;min-height:100vh}
        aside{width:210px;background:#0f172a;color:#cbd5e1;padding:20px 0;flex-shrink:0}
        aside h2{color:#fff;text-align:center;font-size:18px;margin:0 0 20px}
        aside a{display:block;color:#cbd5e1;text-decoration:none;padding:12px 24px}
        aside a:hover,aside a.active{background:#1e293b;color:#38bdf8}
        main{flex:1;padding:24px;overflow:auto}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
        .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
        .stat{background:#fff;border-radius:10px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
        .stat .n{font-size:26px;font-weight:700;color:#0ea5e9}
        .stat .l{color:#64748b;font-size:13px}
        table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1)}
        th,td{padding:10px 12px;border-bottom:1px solid #e2e8f0;text-align:left;font-size:14px}
        th{background:#f8fafc;color:#475569}
        .btn{display:inline-block;background:#0ea5e9;color:#fff;padding:7px 14px;border:none;border-radius:7px;cursor:pointer;text-decoration:none;font-size:14px}
        .btn.danger{background:#ef4444}
        .btn.gray{background:#64748b}
        .btn.sm{padding:4px 10px;font-size:12px}
        input,select,textarea{padding:8px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px}
        .alert{background:#dcfce7;color:#166534;padding:10px 14px;border-radius:8px;margin-bottom:16px}
        .alert.err{background:#fee2e2;color:#991b1b}
        form.inline{display:inline}
        .panel{background:#fff;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.1);margin-bottom:20px}
        label{display:block;font-size:13px;color:#475569;margin-top:10px}
        .badge{padding:2px 8px;border-radius:6px;font-size:12px}
        .b-unused{background:#dbeafe;color:#1e40af}
        .b-used{background:#dcfce7;color:#166534}
        .b-sold{background:#fef9c3;color:#854d0e}
        .b-disabled{background:#fee2e2;color:#991b1b}
    </style>
</head>
<body>
<aside>
    <h2>CardShop</h2>
    @php $r = Route::currentRouteName(); @endphp
    <a href="{{ route('admin.dashboard') }}" class="{{ $r==='admin.dashboard'?'active':'' }}">概览</a>
    <a href="{{ route('admin.products.index') }}" class="{{ str_starts_with($r,'admin.products')?'active':'' }}">商品管理</a>
    <a href="{{ route('admin.cards.index') }}" class="{{ str_starts_with($r,'admin.cards')?'active':'' }}">卡密管理</a>
    <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($r,'admin.users')?'active':'' }}">账号管理</a>
    <a href="{{ route('admin.orders.index') }}" class="{{ str_starts_with($r,'admin.orders')?'active':'' }}">订单管理</a>
</aside>
<main>
    <div class="topbar">
        <h1 style="margin:0;font-size:20px">@yield('title', '管理后台')</h1>
        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
            @csrf
            <button class="btn gray" type="submit">退出</button>
        </form>
    </div>

    @if(session('ok'))<div class="alert">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif

    @yield('content')
</main>
</body>
</html>
