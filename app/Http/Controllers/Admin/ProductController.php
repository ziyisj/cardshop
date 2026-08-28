<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withCount([
            'cardKeys as stock_count' => fn ($q) => $q->where('status', 'unused'),
        ])->orderBy('sort')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Product::create($data);
        return redirect()->route('admin.products.index')->with('ok', '商品已创建');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $product->update($data);
        return redirect()->route('admin.products.index')->with('ok', '商品已更新');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return back()->with('ok', '商品已删除');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'slug'          => 'nullable|string|max:120',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'is_active'     => 'nullable|boolean',
            'sort'          => 'nullable|integer',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']) . '-' . Str::random(4);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort'] = $data['sort'] ?? 0;
        return $data;
    }
}
