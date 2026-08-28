@extends('layouts.admin')
@section('title', '概览')
@section('content')
    <div class="cards">
        <div class="stat"><div class="n">{{ $stats['users'] }}</div><div class="l">总账号</div></div>
        <div class="stat"><div class="n">{{ $stats['active_users'] }}</div><div class="l">有效账号</div></div>
        <div class="stat"><div class="n">{{ $stats['products'] }}</div><div class="l">商品数</div></div>
        <div class="stat"><div class="n">{{ $stats['cards_unused'] }}</div><div class="l">未售卡密</div></div>
        <div class="stat"><div class="n">{{ $stats['cards_used'] }}</div><div class="l">已激活卡密</div></div>
        <div class="stat"><div class="n">{{ $stats['orders_paid'] }}</div><div class="l">已支付订单</div></div>
        <div class="stat"><div class="n">￥{{ number_format($stats['revenue'], 2) }}</div><div class="l">总收入</div></div>
    </div>

    <h2 style="margin-top:24px">最近订单</h2>
    <table>
        <tr><th>订单号</th><th>商品</th><th>数量</th><th>金额</th><th>状态</th><th>时间</th></tr>
        @forelse($recentOrders as $o)
            <tr>
                <td><a href="{{ route('admin.orders.show', $o) }}">{{ $o->order_no }}</a></td>
                <td>{{ $o->product->name ?? '-' }}</td>
                <td>{{ $o->quantity }}</td>
                <td>￥{{ number_format($o->amount, 2) }}</td>
                <td>{{ $o->status }}</td>
                <td>{{ $o->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="6">暂无订单</td></tr>
        @endforelse
    </table>
@endsection
