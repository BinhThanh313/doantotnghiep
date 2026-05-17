<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Dữ liệu thẻ Widgets
        // Tổng doanh thu (chỉ tính đơn hàng 'completed')
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        
        // Đơn hàng đang chờ xử lý ('pending')
        $newOrders = Order::where('status', 'pending')->count();
        
        // Tổng số khách hàng và Sản phẩm đang bán
        $totalUsers = User::count(); 
        $activeProducts = Product::where('is_active', 1)->count();

        // 2. Dữ liệu biểu đồ doanh thu (7 ngày gần nhất)
        $chartLabels = [];
        $chartData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d/m');
            
            // Tính tổng doanh thu của từng ngày
            $chartData[] = Order::where('status', 'completed')
                                ->whereDate('created_at', $date)
                                ->sum('total_amount');
        }

        // 3. Bảng 5 đơn hàng mới nhất
        $recentOrders = Order::orderBy('created_at', 'desc')->take(5)->get();

        return response()->json([
            'widgets' => [
                'total_revenue' => $totalRevenue,
                'new_orders' => $newOrders,
                'total_users' => $totalUsers,
                'active_products' => $activeProducts,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData,
            ],
            'recent_orders' => $recentOrders
        ]);
    }
}