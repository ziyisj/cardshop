@extends('layouts.shop')
@section('title', '订单查询')
@section('content')
    <div class="card">
        <h1>订单查询</h1>
        <form method="GET" action="{{ route('shop.query') }}">
            <label>输入订单号</label>
            <input type="text" name="order_no" value="{{ request('order_no') }}" placeholder="订单号">
            <button class="btn" type="submit" style="margin-top:10px">查询</button>
        </form>

        @if(request('order_no'))
            @if($order)
                <hr style="border-color:#334155;margin:16px 0">
                <p>商品：{{ $order->product->name }} × {{ $order->quantity }}</p>
                <p>状态：{{ $order->status }}</p>
                @if($order->delivered_cards)
                    <p>卡密：</p>
                    <div class="codes">{{ $order->delivered_cards }}</div>
                @endif
            @else
                <p class="alert" style="margin-top:12px">未找到该订单</p>
            @endif
        @endif
    </div>
@endsection
