@extends('layouts.shop')
@section('title', '商品列表')
@section('content')
    <h1>选择商品</h1>
    <div class="grid">
        @forelse($products as $p)
            <div class="card">
                <h3>{{ $p->name }}</h3>
                <p class="muted">{{ $p->description }}</p>
                <p class="muted">有效期 {{ $p->duration_days }} 天 · 库存 {{ $p->stock() }}</p>
                <p class="price">￥{{ number_format($p->price, 2) }}</p>
                <a class="btn" href="{{ route('shop.show', $p->slug) }}">购买</a>
            </div>
        @empty
            <p class="muted">暂无商品</p>
        @endforelse
    </div>
@endsection
