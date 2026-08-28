@extends('layouts.admin')
@section('title', '卡密管理')
@section('content')
    <div class="panel">
        <h3 style="margin-top:0">批量生成卡密</h3>
        <form method="POST" action="{{ route('admin.cards.generate') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div>
                <label>商品</label>
                <select name="product_id" required>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}（{{ $p->duration_days }}天）</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>数量</label>
                <input type="number" name="count" value="10" min="1" max="5000">
            </div>
            <div>
                <label>自定义时长(天，选填)</label>
                <input type="number" name="duration_days" placeholder="默认取商品">
            </div>
            <button class="btn" type="submit">生成</button>
            <a class="btn gray" href="{{ route('admin.cards.export', request()->only('product_id')) }}">导出未售</a>
        </form>
    </div>

    <form method="GET" style="margin-bottom:12px">
        <select name="status" onchange="this.form.submit()">
            <option value="">全部状态</option>
            @foreach(['unused'=>'未售','sold'=>'已售待激活','used'=>'已激活','disabled'=>'作废'] as $k=>$v)
                <option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>
            @endforeach
        </select>
    </form>

    <table>
        <tr><th>ID</th><th>卡密</th><th>商品</th><th>时长</th><th>状态</th><th>使用者</th><th>激活时间</th><th>操作</th></tr>
        @forelse($cards as $c)
            <tr>
                <td>{{ $c->id }}</td>
                <td><code>{{ $c->code }}</code></td>
                <td>{{ $c->product->name ?? '-' }}</td>
                <td>{{ $c->duration_days }}天</td>
                <td><span class="badge b-{{ $c->status }}">{{ $c->status }}</span></td>
                <td>{{ $c->user->username ?? '-' }}</td>
                <td>{{ $c->used_at }}</td>
                <td>
                    @if(in_array($c->status, ['unused','sold']))
                        <form class="inline" method="POST" action="{{ route('admin.cards.disable', $c) }}">
                            @csrf <button class="btn sm gray">作废</button>
                        </form>
                    @endif
                    <form class="inline" method="POST" action="{{ route('admin.cards.destroy', $c) }}"
                          onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn sm danger">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">暂无卡密</td></tr>
        @endforelse
    </table>
    <div style="margin-top:14px">{{ $cards->links() }}</div>
@endsection
