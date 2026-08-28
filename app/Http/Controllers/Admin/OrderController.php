<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $query = Order::with('product');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($no = $request->query('order_no')) {
            $query->where('order_no', 'like', "%{$no}%");
        }
        $orders = $query->latest()->paginate(30)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('product');
        return view('admin.orders.show', compact('order'));
    }

    /** 后台手动确认支付并发货 */
    public function markPaid(Order $order)
    {
        try {
            $this->orders->markPaidAndDeliver($order, 'admin');
        } catch (RuntimeException $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok', '已发货');
    }
}
