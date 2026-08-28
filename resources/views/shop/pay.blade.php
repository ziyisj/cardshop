@extends('layouts.shop')
@section('title', '订单支付')
@section('content')
    <div class="card">
        <h1>订单支付</h1>
        <p>订单号：<code>{{ $order->order_no }}</code></p>
        <p>商品：{{ $order->product->name }} × {{ $order->quantity }}</p>
        <p class="price">应付：￥{{ number_format($order->amount, 2) }}</p>

        @if($order->status === 'pending')
            <p class="muted">此处为演示支付。真实环境请对接支付宝/微信/易支付，回调验签后再发货。</p>
            <form method="POST" action="{{ route('shop.mockPaid', $order->order_no) }}">
                @csrf
                <button class="btn" type="submit">模拟支付成功</button>
            </form>
        @elseif($order->status === 'paid')
            <p class="ok alert">订单已支付</p>
            <a class="btn" href="{{ route('shop.result', $order->order_no) }}">查看卡密</a>
        @else
            <p class="alert">订单状态：{{ $order->status }}</p>
        @endif
    </div>
@endsection
