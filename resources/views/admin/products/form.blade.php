@extends('layouts.admin')
@section('title', $product->exists ? '编辑商品' : '新建商品')
@section('content')
    <div class="panel" style="max-width:520px">
        <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
            @csrf
            @if($product->exists) @method('PUT') @endif

            <label>商品名称</label>
            <input name="name" value="{{ old('name', $product->name) }}" required>

            <label>Slug（留空自动生成）</label>
            <input name="slug" value="{{ old('slug', $product->slug) }}">

            <label>描述</label>
            <textarea name="description" rows="3">{{ old('description', $product->description) }}</textarea>

            <label>价格</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $product->price ?? 0) }}" required>

            <label>授权时长（天）</label>
            <input type="number" name="duration_days" value="{{ old('duration_days', $product->duration_days ?? 30) }}" required>

            <label>排序</label>
            <input type="number" name="sort" value="{{ old('sort', $product->sort ?? 0) }}">

            <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}> 上架</label>

            <div style="margin-top:16px">
                <button class="btn" type="submit">保存</button>
                <a class="btn gray" href="{{ route('admin.products.index') }}">返回</a>
            </div>
        </form>
    </div>
@endsection
