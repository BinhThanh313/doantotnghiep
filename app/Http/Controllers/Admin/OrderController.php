<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user', 'payment', 'shipment.carrier');

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
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(15);

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with([
            'items',
            'user',
            'payment',
            'shipment.carrier',
            'vouchers',
        ])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Cập nhật trạng thái đơn hàng (workflow)
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status'         => 'sometimes|in:pending,processing,ready_to_ship,shipped,delivered,completed,cancelled',
            'payment_status' => 'sometimes|in:unpaid,paid,refunded',
            'notes'          => 'nullable|string',
        ]);

        $oldStatus = $order->status;
        $order->update($data);

        // Gửi thông báo khi đổi status
        if (isset($data['status']) && $data['status'] !== $oldStatus && $order->user_id) {
            $messages = [
                'processing'    => 'Đơn hàng đang được chuẩn bị.',
                'ready_to_ship' => 'Đơn hàng đã sẵn sàng giao cho shipper.',
                'shipped'       => 'Đơn hàng đang trên đường giao đến bạn.',
                'delivered'     => 'Đơn hàng đã được giao thành công!',
                'completed'     => 'Đơn hàng đã hoàn thành. Cảm ơn bạn!',
                'cancelled'     => 'Đơn hàng đã bị hủy.',
            ];

            if (isset($messages[$data['status']])) {
                AppNotification::send(
                    $order->user_id,
                    'order_status_changed',
                    'Cập nhật đơn hàng #' . $order->tracking_number,
                    $messages[$data['status']],
                    $order->id,
                    'order'
                );
            }
        }

        return response()->json($order->load('items', 'shipment', 'payment'));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Đã xóa đơn hàng']);
    }

    /**
     * Thống kê đơn hàng theo trạng thái
     */
    public function stats()
    {
        $stats = Order::selectRaw('status, count(*) as count, sum(total_amount + shipping_fee - discount_amount) as total')
                      ->groupBy('status')
                      ->get();

        return response()->json($stats);
    }
}
