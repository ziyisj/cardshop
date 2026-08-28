@extends('layouts.admin')
@section('title', '订单管理')
@section('content')
    <form method="GET" style="margin-bottom:12px">
        <input name="order_no" value="{{ request('order_no') }}" placeholder="订单号">
        <select name="status">
            <option value="">全部状态</option>
            @foreach(['pending'=>'待支付','paid'=>'已支付','failed'=>'失败','canceled'=>'取消'] as $k=>$v)
                <option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn sm" type="submit">筛选</button>
    </form>

    <table>
        <tr><th>订单号</th><th>商品</th><th>数量</th><th>金额</th><th>状态</th><th>联系</th><th>时间</th><th>操作</th></tr>
        @forelse($orders as $o)
            <tr>
                <td>{{ $o->order_no }}</td>
                <td>{{ $o->product->name ?? '-' }}</td>
                <td>{{ $o->quantity }}</td>
                <td>￥{{ number_format($o->amount, 2) }}</td>
                <td>{{ $o->status }}</td>
                <td>{{ $o->contact ?? '-' }}</td>
                <td>{{ $o->created_at }}</td>
                <td>
                    <a class="btn sm" href="{{ route('admin.orders.show', $o) }}">详情</a>
                    @if($o->status === 'pending')
                        <form class="inline" method="POST" action="{{ route('admin.orders.paid', $o) }}">
                            @csrf <button class="btn sm">发货</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8">暂无订单</td></tr>
        @endforelse
    </table>
    <div style="margin-top:14px">{{ $orders->links() }}</div>
@endsection
