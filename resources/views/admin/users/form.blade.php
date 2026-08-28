@extends('layouts.admin')
@section('title', $user->exists ? '编辑账号' : '新建账号')
@section('content')
    <div class="panel" style="max-width:480px">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if($user->exists) @method('PUT') @endif

            <label>用户名</label>
            @if($user->exists)
                <input value="{{ $user->username }}" disabled>
            @else
                <input name="username" value="{{ old('username') }}" required>
            @endif

            <label>密码 {{ $user->exists ? '（留空则不改）' : '' }}</label>
            <input type="text" name="password" {{ $user->exists ? '' : 'required' }}>

            <label>邮箱</label>
            <input name="email" value="{{ old('email', $user->email) }}">

            <label>到期时间</label>
            <input type="datetime-local" name="expires_at"
                   value="{{ old('expires_at', optional($user->expires_at)->format('Y-m-d\TH:i')) }}">

            <label>最大绑定设备数（0=不限）</label>
            <input type="number" name="max_devices" value="{{ old('max_devices', $user->max_devices ?? 1) }}">

            @if($user->exists)
                <label><input type="checkbox" name="is_banned" value="1" {{ $user->is_banned ? 'checked' : '' }}> 封禁</label>
            @endif

            <div style="margin-top:16px">
                <button class="btn" type="submit">保存</button>
                <a class="btn gray" href="{{ route('admin.users.index') }}">返回</a>
            </div>
        </form>
    </div>
@endsection
