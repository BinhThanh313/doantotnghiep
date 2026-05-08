<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_products' => Product::count(),
            'total_orders'   => Order::count(),
            'total_users'    => User::count(),
            'total_revenue'  => Order::where('status', 'completed')
                                     ->sum('total_amount'),
            'recent_orders'  => Order::with('items')
                                     ->latest()
                                     ->limit(5)
                                     ->get(),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ]);
    }
}