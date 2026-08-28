@extends('layouts.admin')
@section('title', '订单详情')
@section('content')
    <div class="panel" style="max-width:600px">
        <p><b>订单号：</b>{{ $order->order_no }}</p>
        <p><b>商品：</b>{{ $order->product->name ?? '-' }} × {{ $order->quantity }}</p>
        <p><b>金额：</b>￥{{ number_format($order->amount, 2) }}</p>
        <p><b>状态：</b>{{ $order->status }}</p>
        <p><b>支付方式：</b>{{ $order->pay_method ?? '-' }}</p>
        <p><b>交易号：</b>{{ $order->trade_no ?? '-' }}</p>
        <p><b>联系方式：</b>{{ $order->contact ?? '-' }}</p>
        <p><b>买家IP：</b>{{ $order->buyer_ip ?? '-' }}</p>
        <p><b>下单时间：</b>{{ $order->created_at }}</p>
        <p><b>支付时间：</b>{{ $order->paid_at ?? '-' }}</p>
        @if($order->delivered_cards)
            <p><b>已发卡密：</b></p>
            <pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px">{{ $order->delivered_cards }}</pre>
        @endif

        @if($order->status === 'pending')
            <form method="POST" action="{{ route('admin.orders.paid', $order) }}">
                @csrf <button class="btn">确认支付并发货</button>
            </form>
        @endif
        <a class="btn gray" href="{{ route('admin.orders.index') }}" style="margin-top:10px">返回</a>
    </div>
@endsection
