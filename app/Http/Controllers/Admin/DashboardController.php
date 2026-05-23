<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '7days'); // 7days, 30days, thisMonth, thisYear

        [$startDate, $endDate] = $this->getPeriodDates($period);

        // ==================== WIDGETS ====================
        $totalRevenue   = Order::where('status', 'completed')
                               ->whereBetween('created_at', [$startDate, $endDate])
                               ->sum(\DB::raw('total_amount + shipping_fee - discount_amount'));

        $prevRevenue    = Order::where('status', 'completed')
                               ->whereBetween('created_at', $this->getPrevPeriodDates($period))
                               ->sum(\DB::raw('total_amount + shipping_fee - discount_amount'));

        $newOrders      = Order::where('status', 'pending')->count();
        $totalUsers     = User::where('role', '!=', 'admin')->count();
        $activeProducts = Product::where('is_active', 1)->count();
        $lowStock       = Product::where('stock', '<=', 5)->where('stock', '>', 0)->count();
        $outOfStock     = Product::where('stock', 0)->count();

        // ==================== CHART (doanh thu theo ngày) ====================
        $chartLabels = [];
        $chartData   = [];
        $days        = $period === 'thisMonth' ? now()->daysInMonth : ($period === '30days' ? 30 : 7);

        for ($i = $days - 1; $i >= 0; $i--) {
            $date          = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d/m');
            $chartData[]   = Order::where('status', 'completed')
                                  ->whereDate('created_at', $date)
                                  ->sum(\DB::raw('total_amount + shipping_fee - discount_amount'));
        }

        // ==================== TOP PRODUCTS ====================
        $topProducts = OrderItem::select('product_id', 'product_name')
                                ->selectRaw('SUM(quantity) as total_sold, SUM(quantity * price) as total_revenue')
                                ->with('product:id,name,image')
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->groupBy('product_id', 'product_name')
                                ->orderByDesc('total_sold')
                                ->limit(10)
                                ->get();

        // ==================== RECENT ORDERS ====================
        $recentOrders = Order::with('user:id,name')
                             ->orderByDesc('created_at')
                             ->take(8)
                             ->get();

        // ==================== LOW STOCK ALERTS ====================
        $lowStockProducts = Product::where('stock', '<=', 5)
                                   ->orderBy('stock')
                                   ->take(10)
                                   ->get(['id', 'name', 'stock', 'image']);

        // ==================== REVENUE COMPARISON ====================
        $revenueChange = $prevRevenue > 0
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : 0;

        return response()->json([
            'widgets' => [
                'total_revenue'    => $totalRevenue,
                'revenue_change'   => $revenueChange,   // % so với kỳ trước
                'new_orders'       => $newOrders,
                'total_users'      => $totalUsers,
                'active_products'  => $activeProducts,
                'low_stock'        => $lowStock,
                'out_of_stock'     => $outOfStock,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data'   => $chartData,
            ],
            'top_products'      => $topProducts,
            'recent_orders'     => $recentOrders,
            'low_stock_products' => $lowStockProducts,
        ]);
    }

    private function getPeriodDates(string $period): array
    {
        return match($period) {
            '30days'    => [Carbon::today()->subDays(29)->startOfDay(), Carbon::today()->endOfDay()],
            'thisMonth' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'thisYear'  => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default     => [Carbon::today()->subDays(6)->startOfDay(), Carbon::today()->endOfDay()], // 7days
        };
    }

    private function getPrevPeriodDates(string $period): array
    {
        return match($period) {
            '30days'    => [Carbon::today()->subDays(59)->startOfDay(), Carbon::today()->subDays(30)->endOfDay()],
            'thisMonth' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'thisYear'  => [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()],
            default     => [Carbon::today()->subDays(13)->startOfDay(), Carbon::today()->subDays(7)->endOfDay()],
        };
    }
}