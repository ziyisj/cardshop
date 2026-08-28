<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\CardKey;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users'         => AppUser::count(),
            'active_users'  => AppUser::where('expires_at', '>', now())->count(),
            'products'      => Product::count(),
            'cards_unused'  => CardKey::where('status', 'unused')->count(),
            'cards_used'    => CardKey::where('status', 'used')->count(),
            'orders_paid'   => Order::where('status', 'paid')->count(),
            'revenue'       => Order::where('status', 'paid')->sum('amount'),
        ];

        $recentOrders = Order::with('product')->latest()->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
