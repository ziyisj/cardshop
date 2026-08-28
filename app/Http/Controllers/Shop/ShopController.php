<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use RuntimeException;

class ShopController extends Controller
{
    public function __construct(private OrderService $orders) {}

    /** 商品列表首页 */
    public function index()
    {
        $products = Product::where('is_active', true)
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get();

        return view('shop.index', compact('products'));
    }

    /** 商品详情 */
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('shop.show', compact('product'));
    }

    /** 提交下单 */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:100',
            'contact'    => 'nullable|string|max:120',
        ]);

        $product = Product::findOrFail($data['product_id']);

        try {
            $order = $this->orders->createOrder(
                $product,
                (int) $data['quantity'],
                $data['contact'] ?? null,
                $request->ip(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }

        return redirect()->route('shop.pay', $order->order_no);
    }

    /** 支付页（此处演示手动/模拟支付） */
    public function pay(string $orderNo)
    {
        $order = Order::where('order_no', $orderNo)->firstOrFail();
        return view('shop.pay', compact('order'));
    }

    /**
     * 模拟支付成功回调（真实环境替换为支付宝/微信/易支付回调，
     * 并务必校验回调签名后再发货）。
     */
    public function mockPaid(string $orderNo)
    {
        $order = Order::where('order_no', $orderNo)->firstOrFail();

        try {
            $order = $this->orders->markPaidAndDeliver($order, 'mock', 'MOCK'.time());
        } catch (RuntimeException $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }

        return redirect()->route('shop.result', $order->order_no);
    }

    /** 发货结果页：展示卡密 */
    public function result(string $orderNo)
    {
        $order = Order::where('order_no', $orderNo)->firstOrFail();
        return view('shop.result', compact('order'));
    }

    /** 订单查询（凭订单号找回卡密） */
    public function query(Request $request)
    {
        $order = null;
        if ($no = $request->query('order_no')) {
            $order = Order::where('order_no', $no)->first();
        }
        return view('shop.query', compact('order'));
    }
}
