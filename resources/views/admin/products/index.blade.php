@extends('layouts.admin')
@section('title', '商品管理')
@section('content')
    <p><a class="btn" href="{{ route('admin.products.create') }}">+ 新建商品</a></p>
    <table>
        <tr><th>ID</th><th>名称</th><th>价格</th><th>时长(天)</th><th>库存</th><th>状态</th><th>操作</th></tr>
        @forelse($products as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->name }}</td>
                <td>￥{{ number_format($p->price, 2) }}</td>
                <td>{{ $p->duration_days }}</td>
                <td>{{ $p->stock_count }}</td>
                <td>{{ $p->is_active ? '上架' : '下架' }}</td>
                <td>
                    <a class="btn sm" href="{{ route('admin.products.edit', $p) }}">编辑</a>
                    <form class="inline" method="POST" action="{{ route('admin.products.destroy', $p) }}"
                          onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn sm danger">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">暂无商品</td></tr>
        @endforelse
    </table>
    <div style="margin-top:14px">{{ $products->links() }}</div>
@endsection
