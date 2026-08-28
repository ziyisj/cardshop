@extends('layouts.shop')
@section('title', '发货结果')
@section('content')
    <div class="card">
        <h1>发货成功 🎉</h1>
        <p>订单号：<code>{{ $order->order_no }}</code></p>
        <p>商品：{{ $order->product->name }} × {{ $order->quantity }}</p>
        @if($order->delivered_cards)
            <p>您的卡密（请妥善保存）：</p>
            <div class="codes">{{ $order->delivered_cards }}</div>
        @else
            <p class="muted">尚未发货。</p>
        @endif
        <p class="muted" style="margin-top:16px">
            使用方法：在程序中输入卡密激活账号，或在官网/程序内兑换。
        </p>
        <a class="btn" href="{{ route('shop.index') }}" style="margin-top:12px">继续购买</a>
    </div>
@endsection
