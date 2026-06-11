<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '7days'); // 7days, 30days, thisMonth, thisYear
        [$startDate, $endDate] = $this->getPeriodDates($period);
        $today = Carbon::today();

        // ==================== WIDGETS (Doanh thu & Tổng quan) ====================
        // Tổng doanh thu kỳ hiện tại
        $totalRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        // Tổng doanh thu kỳ trước (để so sánh)
        $prevRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', $this->getPrevPeriodDates($period))
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        $revenueChange = $prevRevenue > 0 
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) 
            : 0;

        $newOrders      = Order::where('status', 'pending')->count();
        $totalUsers     = User::where('role', '!=', 'admin')->count(); // Chỉ đếm khách hàng
        $activeProducts = Product::where('is_active', true)->count();
        $outOfStock     = Product::where('stock', 0)->count();

        // ==================== TODAY STATS ====================
        $todayOrders  = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        // ==================== CHART (Doanh thu theo ngày) ====================
        // Tối ưu: Lấy toàn bộ dữ liệu trong 1 query duy nhất (Từ File 2)
        $chartRaw = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('revenue', 'date');

        $chartLabels = [];
        $chartData   = [];
        $currentDate = $startDate->copy();

        // Lấp đầy các ngày không có đơn hàng bằng số 0
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // Nếu là 'thisYear', bạn có thể muốn đổi format hiển thị thành tháng (m/Y) thay vì ngày
            $chartLabels[] = $period === 'thisYear' ? $currentDate->format('d/m/Y') : $currentDate->format('d/m');
            $chartData[]   = $chartRaw[$dateString] ?? 0;
            
            $currentDate->addDay();
        }

        // ==================== TOP PRODUCTS ====================
        $topProducts = OrderItem::select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as total_sold, SUM(quantity * price) as total_revenue')
            ->with('product:id,name,image')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue') // Sắp xếp theo tổng tiền mang lại (từ File 2)
            ->limit(10)
            ->get();

        // ==================== RECENT ORDERS ====================
        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->limit(8)
            ->get([
                'id', 'user_id', 'tracking_number', 'customer_name', 'total_amount', 
                'shipping_fee', 'discount_amount', 'status', 'payment_status', 'created_at'
            ]);

        // ==================== LOW STOCK ALERTS ====================
        $lowStockProducts = Product::where('is_active', true)
            ->where('stock', '<=', 5)
            ->where('stock', '>', 0) // Loại trừ các sản phẩm đã hết hàng hẳn (out of stock)
            ->orderBy('stock')
            ->limit(10)
            ->get(['id', 'name', 'sku', 'stock', 'price', 'image']);

        return response()->json([
            'widgets' => [
                'total_revenue'   => $totalRevenue,
                'revenue_change'  => $revenueChange,
                'new_orders'      => $newOrders,
                'total_users'     => $totalUsers,
                'active_products' => $activeProducts,
                'low_stock'       => $lowStockProducts->count(),
                'out_of_stock'    => $outOfStock,
            ],
            'today' => [
                'today_orders'  => $todayOrders,
                'today_revenue' => $todayRevenue,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data'   => $chartData,
            ],
            'top_products'       => $topProducts,
            'recent_orders'      => $recentOrders,
            'low_stock'          => $lowStockProducts,   
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