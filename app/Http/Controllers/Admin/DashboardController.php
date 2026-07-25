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
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '7days'); // 7days, 30days, thisMonth, thisYear
        [$startDate, $endDate] = $this->getPeriodDates($period);
        $today = Carbon::today();

        // ==================== WIDGETS (Doanh thu & Tổng quan) ====================
        // Cache chỉ các chỉ số tính toán nặng (60s)
        $cacheKey = "admin_dashboard_stats_{$period}_{$today->toDateString()}";
        
        $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($period, $startDate, $endDate, $today) {
            $totalRevenue = Order::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
                ->value('rev');

            $prevRevenue = Order::where('status', 'completed')
                ->whereBetween('created_at', $this->getPrevPeriodDates($period))
                ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
                ->value('rev');

            $revenueChange = $prevRevenue > 0 
                ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) 
                : 0;

            $newOrders      = Order::where('status', 'pending')->count();
            $totalUsers     = User::where('role', '!=', 'admin')->count(); 
            $activeProducts = Product::where('is_active', true)->count();
            $outOfStock     = Product::where('stock', 0)->count();

            $todayOrders  = Order::whereDate('created_at', $today)->count();
            $todayRevenue = Order::where('status', 'completed')
                ->whereDate('created_at', $today)
                ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
                ->value('rev');

            $chartRaw = Order::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('CAST(created_at AS DATE) as date, COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as revenue')
                ->groupBy(DB::raw('CAST(created_at AS DATE)'))
                ->orderBy('date')
                ->pluck('revenue', 'date');

            $chartLabels = [];
            $chartData   = [];
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');
                $chartLabels[] = $period === 'thisYear' ? $currentDate->format('d/m/Y') : $currentDate->format('d/m');
                $chartData[]   = $chartRaw[$dateString] ?? 0;
                $currentDate->addDay();
            }

            return compact(
                'totalRevenue', 'prevRevenue', 'revenueChange', 
                'newOrders', 'totalUsers', 'activeProducts', 'outOfStock',
                'todayOrders', 'todayRevenue', 
                'chartLabels', 'chartData'
            );
        });

        extract($stats);

        // ==================== TOP PRODUCTS (KHÔNG CACHE ĐỂ TRÁNH LỖI) ====================
        $topProducts = OrderItem::select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as total_sold, SUM(quantity * price) as total_revenue')
            ->with('product:id,name,image')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
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
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->limit(10)
            ->get(['id', 'name', 'stock', 'image', 'price']);

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
            'today_orders'  => $todayOrders,
            'today_revenue' => $todayRevenue,
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

    /**
     * GET /api/admin/dashboard/export?period=7days
     * Xuất báo cáo tổng hợp dashboard ra 1 file .xlsx nhiều sheet — bao phủ
     * toàn bộ nội dung hiển thị trên trang dashboard:
     * Tổng quan, Doanh thu theo ngày, Trạng thái đơn hàng, Phương thức
     * thanh toán, Top sản phẩm, Đơn hàng gần đây, Tồn kho thấp.
     */
    public function export(Request $request)
    {
        $period = $request->get('period', '7days');
        [$startDate, $endDate] = $this->getPeriodDates($period);
        $today = Carbon::today();
        $periodLabels = [
            '7days'     => '7 ngày qua',
            '30days'    => '30 ngày qua',
            'thisMonth' => 'Tháng này',
            'thisYear'  => 'Năm nay',
        ];

        $totalRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        $prevRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', $this->getPrevPeriodDates($period))
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        $revenueChange = $prevRevenue > 0
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : 0;

        $newOrders      = Order::where('status', 'pending')->count();
        $totalUsers     = User::where('role', '!=', 'admin')->count();
        $activeProducts = Product::where('is_active', true)->count();
        $outOfStock     = Product::where('stock', 0)->count();

        $todayOrders  = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        $chartRaw = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('CAST(created_at AS DATE) as date, COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as revenue, COUNT(*) as orders')
            ->groupBy(DB::raw('CAST(created_at AS DATE)'))
            ->orderBy('date')
            ->get();

        // Giống hệt dữ liệu 2 biểu đồ "Phân bổ trạng thái đơn hàng" và
        // "Phương thức thanh toán" trên dashboard — cả 2 đều lấy TOÀN BỘ
        // đơn hàng (không lọc theo period), khớp với OrderController@stats.
        $byStatus = Order::selectRaw('status, COUNT(*) as count, COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as total')
            ->groupBy('status')
            ->get();

        $byPaymentMethod = Order::selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        $statusLabels = [
            'pending'       => 'Chờ xử lý',
            'processing'    => 'Đang chuẩn bị',
            'ready_to_ship' => 'Sẵn sàng giao',
            'shipped'       => 'Đang vận chuyển',
            'delivered'     => 'Đã giao hàng',
            'completed'     => 'Hoàn thành',
            'cancelled'     => 'Đã hủy',
        ];

        $topProducts = OrderItem::select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as total_sold, SUM(quantity * price) as total_revenue')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->limit(20)
            ->get(['id', 'user_id', 'tracking_number', 'customer_name', 'total_amount', 'shipping_fee', 'discount_amount', 'status', 'payment_status', 'created_at']);

        // Cột 'sku' có thể chưa tồn tại trên một số DB — chọn cột động để
        // không bao giờ vỡ query nếu thiếu cột này.
        $hasSku = Schema::hasColumn('products', 'sku');
        $lowStockColumns = $hasSku ? ['id', 'name', 'sku', 'stock', 'price'] : ['id', 'name', 'stock', 'price'];

        $lowStockProducts = Product::where('is_active', true)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->get($lowStockColumns);

        // Tổng số đơn / tổng SP tồn kho thấp — dùng để tính cột "Tỷ lệ" (%)
        $totalOrdersAllStatus  = $byStatus->sum('count');
        $totalOrdersAllPayment = $byPaymentMethod->sum('count');
        $paymentLabelsMap = ['cod' => 'COD', 'bank' => 'Chuyển khoản'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ---- Sheet 1: Tổng quan ----
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tổng quan');
        $sheet->fromArray([
            ['Báo cáo tổng quan dashboard', $periodLabels[$period] ?? $period],
            ['Xuất lúc', now()->format('d/m/Y H:i')],
            [],
            ['Chỉ số', 'Giá trị'],
            ['Tổng doanh thu', (float) $totalRevenue],
            ['Đơn chờ xử lý', $newOrders],
            ['Khách hàng', $totalUsers],
            ['Sản phẩm hoạt động', $activeProducts],
            ['Đơn hôm nay', $todayOrders],
            ['Doanh thu hôm nay', (float) $todayRevenue],
        ], null, 'A1');
        $sheet->getStyle('A4:B4')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        foreach (['A', 'B'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 2: Doanh thu theo ngày ----
        $revenueSheet = $spreadsheet->createSheet();
        $revenueSheet->setTitle('Doanh thu theo ngày');
        $revenueSheet->fromArray(['Ngày', 'Doanh thu', 'Số đơn'], null, 'A1');
        $revenueSheet->getStyle('A1:C1')->getFont()->setBold(true);
        $row = 2;
        foreach ($chartRaw as $r) {
            $revenueSheet->fromArray([
                \Carbon\Carbon::parse($r->date)->format('d/m'),
                (float) $r->revenue,
                (int) $r->orders,
            ], null, 'A' . $row);
            $row++;
        }
        foreach (['A', 'B', 'C'] as $col) {
            $revenueSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 3: Phương thức thanh toán ----
        $paymentSheet = $spreadsheet->createSheet();
        $paymentSheet->setTitle('Phương thức thanh toán');
        $paymentSheet->fromArray(['Phương thức', 'Số đơn', 'Tỷ lệ'], null, 'A1');
        $paymentSheet->getStyle('A1:C1')->getFont()->setBold(true);
        $row = 2;
        foreach ($byPaymentMethod as $p) {
            $pct = $totalOrdersAllPayment > 0 ? round(($p->count / $totalOrdersAllPayment) * 100) : 0;
            $paymentSheet->fromArray([
                $paymentLabelsMap[$p->payment_method] ?? strtoupper($p->payment_method ?? 'Khác'),
                (int) $p->count,
                $pct . '%',
            ], null, 'A' . $row);
            $row++;
        }
        foreach (['A', 'B', 'C'] as $col) {
            $paymentSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 4: Trạng thái đơn hàng ----
        $statusSheet = $spreadsheet->createSheet();
        $statusSheet->setTitle('Trạng thái đơn hàng');
        $statusSheet->fromArray(['Trạng thái', 'Số lượng', 'Tỷ lệ'], null, 'A1');
        $statusSheet->getStyle('A1:C1')->getFont()->setBold(true);
        $row = 2;
        foreach ($byStatus as $s) {
            $pct = $totalOrdersAllStatus > 0 ? round(($s->count / $totalOrdersAllStatus) * 100) : 0;
            $statusSheet->fromArray([
                $statusLabels[$s->status] ?? $s->status,
                (int) $s->count,
                $pct . '%',
            ], null, 'A' . $row);
            $row++;
        }
        foreach (['A', 'B', 'C'] as $col) {
            $statusSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 5: Đơn hàng mới nhất ----
        $ordersSheet = $spreadsheet->createSheet();
        $ordersSheet->setTitle('Đơn hàng mới nhất');
        $ordersSheet->fromArray(['Mã đơn', 'Khách hàng', 'Giá trị', 'Trạng thái', 'Ngày'], null, 'A1');
        $ordersSheet->getStyle('A1:E1')->getFont()->setBold(true);
        $row = 2;
        foreach ($recentOrders as $o) {
            $ordersSheet->fromArray([
                $o->tracking_number ?? '#' . $o->id,
                $o->customer_name,
                (float) ($o->total_amount + $o->shipping_fee - $o->discount_amount),
                $statusLabels[$o->status] ?? $o->status,
                $o->created_at->format('d/m/Y'),
            ], null, 'A' . $row);
            $row++;
        }
        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $ordersSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 6: Cảnh báo tồn kho ----
        $stockSheet = $spreadsheet->createSheet();
        $stockSheet->setTitle('Cảnh báo tồn kho');
        $stockSheet->fromArray(['Sản phẩm', 'SKU', 'Tồn kho', 'Trạng thái'], null, 'A1');
        $stockSheet->getStyle('A1:D1')->getFont()->setBold(true);
        $row = 2;
        foreach ($lowStockProducts as $p) {
            $stockSheet->fromArray([
                $p->name,
                $hasSku ? ($p->sku ?? 'N/A') : 'N/A',
                (int) $p->stock,
                (int) $p->stock === 0 ? 'Hết hàng' : 'Sắp hết',
            ], null, 'A' . $row);
            $row++;
        }
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $stockSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 7 (bổ sung): Top sản phẩm ----
        $topSheet = $spreadsheet->createSheet();
        $topSheet->setTitle('Top sản phẩm');
        $topSheet->fromArray(['Sản phẩm', 'Số lượng bán', 'Doanh thu'], null, 'A1');
        $topSheet->getStyle('A1:C1')->getFont()->setBold(true);
        $row = 2;
        foreach ($topProducts as $p) {
            $topSheet->fromArray([$p->product_name, (int) $p->total_sold, (float) $p->total_revenue], null, 'A' . $row);
            $row++;
        }
        foreach (['A', 'B', 'C'] as $col) {
            $topSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'dashboard_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * GET /api/admin/dashboard/export-pdf?period=7days
     * Xuất báo cáo dashboard ra file PDF — giữ nguyên giao diện dashboard
     * (các thẻ KPI, biểu đồ doanh thu theo ngày, biểu đồ phân bổ trạng thái
     * đơn hàng, biểu đồ phương thức thanh toán) kèm 2 bảng tóm tắt
     * (đơn hàng mới nhất, cảnh báo tồn kho thấp).
     */
    public function exportPdf(Request $request)
    {
        $period = $request->get('period', '7days');
        [$startDate, $endDate] = $this->getPeriodDates($period);
        $today = Carbon::today();
        $periodLabels = [
            '7days'     => '7 ngày qua',
            '30days'    => '30 ngày qua',
            'thisMonth' => 'Tháng này',
            'thisYear'  => 'Năm nay',
        ];

        $totalRevenue = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        $newOrders      = Order::where('status', 'pending')->count();
        $totalUsers     = User::where('role', '!=', 'admin')->count();
        $activeProducts = Product::where('is_active', true)->count();

        $todayOrders  = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->selectRaw('COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as rev')
            ->value('rev');

        $chartRaw = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('CAST(created_at AS DATE) as date, COALESCE(SUM(total_amount + shipping_fee - discount_amount), 0) as revenue, COUNT(*) as orders')
            ->groupBy(DB::raw('CAST(created_at AS DATE)'))
            ->orderBy('date')
            ->get();
        $maxRevenue = $chartRaw->max('revenue') ?: 1;

        $byStatus = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        $totalOrdersAllStatus = $byStatus->sum('count');

        $byPaymentMethod = Order::selectRaw('payment_method, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();
        $totalOrdersAllPayment = $byPaymentMethod->sum('count');

        $statusLabels = [
            'pending'       => 'Chờ xử lý',
            'processing'    => 'Đang chuẩn bị',
            'ready_to_ship' => 'Sẵn sàng giao',
            'shipped'       => 'Đang vận chuyển',
            'delivered'     => 'Đã giao hàng',
            'completed'     => 'Hoàn thành',
            'cancelled'     => 'Đã hủy',
        ];
        $statusColors = [
            'pending'       => '#f59e0b',
            'processing'    => '#3b82f6',
            'ready_to_ship' => '#8b5cf6',
            'shipped'       => '#6366f1',
            'delivered'     => '#14b8a6',
            'completed'     => '#10b981',
            'cancelled'     => '#ef4444',
        ];
        $paymentLabelsMap = ['cod' => 'COD', 'bank' => 'Chuyển khoản'];
        $paymentColors    = ['cod' => '#f59e0b', 'bank' => '#3b82f6'];

        $statusDistribution = $byStatus->map(function ($s) use ($totalOrdersAllStatus, $statusLabels, $statusColors) {
            return [
                'label' => $statusLabels[$s->status] ?? $s->status,
                'count' => $s->count,
                'pct'   => $totalOrdersAllStatus > 0 ? round(($s->count / $totalOrdersAllStatus) * 100) : 0,
                'color' => $statusColors[$s->status] ?? '#6b7280',
            ];
        });

        $paymentDistribution = $byPaymentMethod->map(function ($p) use ($totalOrdersAllPayment, $paymentLabelsMap, $paymentColors) {
            return [
                'label' => $paymentLabelsMap[$p->payment_method] ?? strtoupper($p->payment_method ?? 'Khác'),
                'count' => $p->count,
                'pct'   => $totalOrdersAllPayment > 0 ? round(($p->count / $totalOrdersAllPayment) * 100) : 0,
                'color' => $paymentColors[$p->payment_method] ?? '#6b7280',
            ];
        });

        $recentOrders = Order::latest()
            ->limit(8)
            ->get(['id', 'tracking_number', 'customer_name', 'total_amount', 'shipping_fee', 'discount_amount', 'status', 'created_at']);

        $hasSku = Schema::hasColumn('products', 'sku');
        $lowStockColumns = $hasSku ? ['id', 'name', 'sku', 'stock'] : ['id', 'name', 'stock'];

        $lowStockProducts = Product::where('is_active', true)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->limit(10)
            ->get($lowStockColumns);

        $html = view('admin.reports.dashboard-pdf', [
            'periodLabel'         => $periodLabels[$period] ?? $period,
            'generatedAt'         => now()->format('d/m/Y H:i'),
            'totalRevenue'        => $totalRevenue,
            'newOrders'           => $newOrders,
            'totalUsers'          => $totalUsers,
            'activeProducts'      => $activeProducts,
            'todayOrders'         => $todayOrders,
            'todayRevenue'        => $todayRevenue,
            'chartRaw'            => $chartRaw,
            'maxRevenue'          => $maxRevenue,
            'statusDistribution'  => $statusDistribution,
            'paymentDistribution' => $paymentDistribution,
            'recentOrders'        => $recentOrders,
            'lowStockProducts'    => $lowStockProducts,
            'statusLabels'        => $statusLabels,
        ])->render();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans'); // font có dấu tiếng Việt

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $filename = 'dashboard_' . now()->format('Ymd_His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
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
