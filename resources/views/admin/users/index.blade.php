@extends('layouts.admin')
@section('title', '账号管理')
@section('content')
    <div style="display:flex;justify-content:space-between;margin-bottom:12px">
        <form method="GET">
            <input name="kw" value="{{ request('kw') }}" placeholder="搜索用户名/邮箱">
            <button class="btn sm" type="submit">搜索</button>
        </form>
        <a class="btn" href="{{ route('admin.users.create') }}">+ 新建账号</a>
    </div>

    <table>
        <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>到期时间</th><th>设备</th><th>状态</th><th>操作</th></tr>
        @forelse($users as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->username }}</td>
                <td>{{ $u->email ?? '-' }}</td>
                <td>{{ $u->expires_at ?? '未激活' }}</td>
                <td>{{ $u->machine_code ? '已绑定' : '-' }}</td>
                <td>
                    @if($u->is_banned)<span class="badge b-disabled">封禁</span>
                    @elseif($u->isActive())<span class="badge b-used">有效</span>
                    @else<span class="badge b-sold">过期</span>@endif
                </td>
                <td>
                    <a class="btn sm" href="{{ route('admin.users.edit', $u) }}">编辑</a>
                    <form class="inline" method="POST" action="{{ route('admin.users.extend', $u) }}">
                        @csrf
                        <input type="number" name="days" value="30" min="1" style="width:60px" class="sm">
                        <button class="btn sm">续期</button>
                    </form>
                    @if($u->machine_code)
                    <form class="inline" method="POST" action="{{ route('admin.users.unbind', $u) }}">
                        @csrf <button class="btn sm gray">解绑</button>
                    </form>
                    @endif
                    <form class="inline" method="POST" action="{{ route('admin.users.destroy', $u) }}"
                          onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn sm danger">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">暂无账号</td></tr>
        @endforelse
    </table>
    <div style="margin-top:14px">{{ $users->links() }}</div>
@endsection
