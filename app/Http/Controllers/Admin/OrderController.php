<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderRefundNotification;
use App\Mail\OrderStatusUpdated;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OrderController extends Controller
{
    // ==================== INDEX ====================

    public function index(Request $request)
    {
        $query = Order::with(['user', 'payment', 'shipment.carrier']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_email', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Sorting
        $sortBy    = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['created_at', 'total_amount', 'status', 'customer_name', 'tracking_number'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $orders = $query->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    // ==================== SHOW ====================

    public function show($id)
    {
        $order = Order::with([
            'items.product',
            'user',
            'payment',
            'shipment.carrier',
            'vouchers',
        ])->findOrFail($id);

        return response()->json($order);
    }

    // ==================== UPDATE ====================

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status'         => 'sometimes|in:pending,processing,ready_to_ship,shipped,delivered,completed,cancelled',
            'payment_status' => 'sometimes|in:unpaid,paid,refunded',
            'notes'          => 'nullable|string|max:1000',
            'tracking_number'=> 'nullable|string|max:100',
        ]);

        $oldStatus = $order->status;
        $order->update($data);

        // Gửi thông báo in-app + email khi đổi status
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $this->sendStatusNotification($order, $data['status']);
        }

        return response()->json($order->load('items', 'shipment', 'payment'));
    }

    // ==================== DESTROY ====================

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Đã xóa đơn hàng']);
    }

    // ==================== BULK ACTIONS ====================

    /**
     * POST /api/admin/orders/bulk
     * body: { ids: [1,2,3], action: 'delete'|'update_status', status: '...' }
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:orders,id',
            'action' => 'required|in:delete,update_status,update_payment_status',
            'status' => 'sometimes|in:pending,processing,ready_to_ship,shipped,delivered,completed,cancelled',
            'payment_status' => 'sometimes|in:unpaid,paid,refunded',
        ]);

        $ids = $request->ids;

        switch ($request->action) {
            case 'delete':
                DB::transaction(function () use ($ids) {
                    \App\Models\OrderItem::whereIn('order_id', $ids)->delete();
                    Order::whereIn('id', $ids)->delete();
                });
                return response()->json(['message' => 'Đã xóa ' . count($ids) . ' đơn hàng']);

            case 'update_status':
                $request->validate(['status' => 'required']);
                Order::whereIn('id', $ids)->update(['status' => $request->status]);
                return response()->json(['message' => 'Đã cập nhật trạng thái ' . count($ids) . ' đơn hàng']);

            case 'update_payment_status':
                $request->validate(['payment_status' => 'required']);
                Order::whereIn('id', $ids)->update(['payment_status' => $request->payment_status]);
                return response()->json(['message' => 'Đã cập nhật thanh toán ' . count($ids) . ' đơn hàng']);
        }
    }

    // ==================== STATS ====================

    public function stats(Request $request)
    {
        // Thống kê theo trạng thái
        $byStatus = Order::selectRaw('status, count(*) as count, COALESCE(sum(total_amount + shipping_fee - discount_amount), 0) as total')
                         ->groupBy('status')
                         ->get();

        // Thống kê tổng quan
        $overview = [
            'total_orders'   => Order::count(),
            'total_revenue'  => Order::where('status', 'completed')
                                     ->selectRaw('COALESCE(sum(total_amount + shipping_fee - discount_amount), 0) as rev')
                                     ->value('rev'),
            'pending_count'  => Order::where('status', 'pending')->count(),
            'today_orders'   => Order::whereDate('created_at', today())->count(),
            'today_revenue'  => Order::where('status', 'completed')
                                     ->whereDate('created_at', today())
                                     ->selectRaw('COALESCE(sum(total_amount + shipping_fee - discount_amount), 0) as rev')
                                     ->value('rev'),
        ];

        // Biểu đồ doanh thu 30 ngày
        $revenueChart = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COALESCE(sum(total_amount + shipping_fee - discount_amount), 0) as revenue, count(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Thống kê phương thức thanh toán
        $paymentMethods = Order::selectRaw('payment_method, count(*) as count')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'by_status'      => $byStatus,
            'overview'       => $overview,
            'revenue_chart'  => $revenueChart,
            'payment_methods'=> $paymentMethods,
        ]);
    }

    // ==================== REFUND ====================

    /**
     * POST /api/admin/orders/{id}/refund
     * body: { reason: '...', refund_amount: 50000 }
     */
    public function refund(Request $request, $id)
    {
        $order = Order::with('user')->findOrFail($id);

        $request->validate([
            'reason'        => 'required|string|max:500',
            'refund_amount' => 'required|numeric|min:1|max:' . ($order->total_amount + $order->shipping_fee),
        ]);

        if (!in_array($order->status, ['delivered', 'completed'])) {
            return response()->json(['message' => 'Chỉ có thể hoàn tiền đơn hàng đã giao hoặc hoàn thành'], 422);
        }

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'payment_status' => 'refunded',
                'return_status'  => 'refunded',
                'return_reason'  => $request->reason,
                'refund_amount'  => $request->refund_amount,
                'status'         => 'cancelled',
            ]);

            // Ghi nhận vào payment
            if ($order->payment) {
                $order->payment->update(['status' => 'refunded']);
            }

            // Gửi email thông báo hoàn tiền
            if ($order->customer_email) {
                try {
                    Mail::to($order->customer_email)
                        ->queue(new OrderRefundNotification($order, $request->reason, $request->refund_amount));
                } catch (\Exception $e) {
                    Log::error('Refund email failed: ' . $e->getMessage());
                }
            }

            // In-app notification
            if ($order->user_id) {
                AppNotification::send(
                    $order->user_id,
                    'order_refunded',
                    'Hoàn tiền đơn hàng #' . $order->tracking_number,
                    'Đơn hàng của bạn đã được hoàn tiền ' . number_format($request->refund_amount) . 'đ',
                    $order->id,
                    'order'
                );
            }
        });

        return response()->json([
            'message' => 'Đã xử lý hoàn tiền thành công',
            'order'   => $order->fresh(),
        ]);
    }

    // ==================== EXPORT ====================

    /**
     * GET /api/admin/orders/export?format=csv&status=...&date_from=...
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'sometimes|in:csv,excel',
        ]);

        $query = Order::with(['items', 'payment', 'shipment']);

        // Áp dụng filters giống index
        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $request->search . '%');
            });
        }

        $orders = $query->latest()->get();

        $format = $request->get('format', 'csv');

        if ($format === 'csv') {
            return $this->exportCsv($orders);
        }

        return $this->exportCsv($orders); // mở rộng Excel sau
    }

    private function exportCsv($orders)
    {
        $statusLabels = [
            'pending'       => 'Chờ xử lý',
            'processing'    => 'Đang chuẩn bị',
            'ready_to_ship' => 'Sẵn sàng giao',
            'shipped'       => 'Đang vận chuyển',
            'delivered'     => 'Đã giao hàng',
            'completed'     => 'Hoàn thành',
            'cancelled'     => 'Đã hủy',
        ];

        $paymentLabels = [
            'unpaid'   => 'Chưa thanh toán',
            'paid'     => 'Đã thanh toán',
            'refunded' => 'Hoàn tiền',
        ];

        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($orders, $statusLabels, $paymentLabels) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 cho Excel đọc tiếng Việt
            fputs($file, "\xEF\xBB\xBF");

            // Header row
            fputcsv($file, [
                'Mã đơn hàng', 'Khách hàng', 'Email', 'Điện thoại',
                'Địa chỉ', 'Tỉnh/Thành', 'Tạm tính', 'Phí ship',
                'Giảm giá', 'Tổng cộng', 'Trạng thái', 'Thanh toán',
                'PTTT', 'Ngày tạo', 'Cập nhật lần cuối',
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->tracking_number ?? '#' . $order->id,
                    $order->customer_name,
                    $order->customer_email,
                    $order->customer_phone,
                    $order->address,
                    $order->province ?? '',
                    $order->total_amount,
                    $order->shipping_fee ?? 0,
                    $order->discount_amount ?? 0,
                    ($order->total_amount + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0)),
                    $statusLabels[$order->status] ?? $order->status,
                    $paymentLabels[$order->payment_status] ?? $order->payment_status,
                    strtoupper($order->payment_method ?? ''),
                    $order->created_at->format('d/m/Y H:i'),
                    $order->updated_at->format('d/m/Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==================== REPORT ====================

    /**
     * GET /api/admin/orders/report?period=daily|monthly&year=2026&month=5
     */
    public function report(Request $request)
    {
        $request->validate([
            'period' => 'sometimes|in:daily,monthly,yearly',
            'year'   => 'sometimes|integer|min:2020|max:2030',
            'month'  => 'sometimes|integer|min:1|max:12',
        ]);

        $period = $request->get('period', 'monthly');
        $year   = $request->get('year', now()->year);
        $month  = $request->get('month', now()->month);

        $query = Order::query();

        switch ($period) {
            case 'daily':
                // Báo cáo theo ngày trong 1 tháng
                $query->whereYear('created_at', $year)
                      ->whereMonth('created_at', $month);
                $data = $query->selectRaw(
                    'DAY(created_at) as day,
                     count(*) as total_orders,
                     sum(case when status = "completed" then 1 else 0 end) as completed,
                     sum(case when status = "cancelled" then 1 else 0 end) as cancelled,
                     COALESCE(sum(case when status = "completed" then total_amount + shipping_fee - discount_amount else 0 end), 0) as revenue'
                )->groupByRaw('DAY(created_at)')->orderByRaw('DAY(created_at)')->get();
                break;

            case 'monthly':
                // Báo cáo theo tháng trong 1 năm
                $query->whereYear('created_at', $year);
                $data = $query->selectRaw(
                    'MONTH(created_at) as month,
                     count(*) as total_orders,
                     sum(case when status = "completed" then 1 else 0 end) as completed,
                     sum(case when status = "cancelled" then 1 else 0 end) as cancelled,
                     COALESCE(sum(case when status = "completed" then total_amount + shipping_fee - discount_amount else 0 end), 0) as revenue'
                )->groupByRaw('MONTH(created_at)')->orderByRaw('MONTH(created_at)')->get();
                break;

            case 'yearly':
                $data = $query->selectRaw(
                    'YEAR(created_at) as year,
                     count(*) as total_orders,
                     sum(case when status = "completed" then 1 else 0 end) as completed,
                     sum(case when status = "cancelled" then 1 else 0 end) as cancelled,
                     COALESCE(sum(case when status = "completed" then total_amount + shipping_fee - discount_amount else 0 end), 0) as revenue'
                )->groupByRaw('YEAR(created_at)')->orderByRaw('YEAR(created_at)')->get();
                break;
        }

        return response()->json([
            'period' => $period,
            'year'   => $year,
            'month'  => $month,
            'data'   => $data,
        ]);
    }

    // ==================== PRIVATE HELPERS ====================

    private function sendStatusNotification(Order $order, string $newStatus): void
    {
        $messages = [
            'processing'    => 'Đơn hàng đang được chuẩn bị.',
            'ready_to_ship' => 'Đơn hàng đã sẵn sàng giao cho shipper.',
            'shipped'       => 'Đơn hàng đang trên đường giao đến bạn.',
            'delivered'     => 'Đơn hàng đã được giao thành công!',
            'completed'     => 'Đơn hàng đã hoàn thành. Cảm ơn bạn!',
            'cancelled'     => 'Đơn hàng đã bị hủy.',
        ];

        if (!isset($messages[$newStatus])) return;

        // In-app notification
        if ($order->user_id) {
            AppNotification::send(
                $order->user_id,
                'order_status_changed',
                'Cập nhật đơn hàng #' . $order->tracking_number,
                $messages[$newStatus],
                $order->id,
                'order'
            );
        }

        // Email notification
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)
                    ->queue(new OrderStatusUpdated($order, $newStatus, $messages[$newStatus]));
            } catch (\Exception $e) {
                Log::error('Order status email failed: ' . $e->getMessage());
            }
        }
    }
}