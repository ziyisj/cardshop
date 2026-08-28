@extends('layouts.shop')
@section('title', $product->name)
@section('content')
    <a href="{{ route('shop.index') }}" class="muted">← 返回列表</a>
    <div class="card" style="margin-top:16px">
        <h1>{{ $product->name }}</h1>
        <p class="muted">{{ $product->description }}</p>
        <p>授权时长：{{ $product->duration_days }} 天</p>
        <p>库存：{{ $product->stock() }}</p>
        <p class="price">￥{{ number_format($product->price, 2) }}</p>

        <form method="POST" action="{{ route('shop.checkout') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <label>购买数量</label>
            <input type="number" name="quantity" value="1" min="1" max="100">
            <label>联系方式（邮箱/QQ，用于找回卡密）</label>
            <input type="text" name="contact" placeholder="选填">
            <button class="btn" type="submit" style="margin-top:12px">立即下单</button>
        </form>
    </div>
@endsection
