<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;

/**
 * Đồng bộ lại bản ghi payments.status cho các đơn có orders.payment_status
 * đã là 'paid'/'refunded' nhưng payment record vẫn còn 'pending' (dữ liệu cũ
 * bị lệch từ trước khi sửa ShippingController/OrderController).
 *
 * Chạy: php artisan payments:sync
 * Xem trước (không ghi DB): php artisan payments:sync --dry-run
 */
class SyncPaymentStatuses extends Command
{
    protected $signature = 'payments:sync {--dry-run : Chỉ liệt kê, không cập nhật}';

    protected $description = 'Đồng bộ payments.status theo orders.payment_status cho dữ liệu cũ bị lệch';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Cảnh báo nếu 1 đơn có nhiều hơn 1 bản ghi payment (không nên xảy ra,
        // nhưng nếu có thì $order->payment (hasOne) chỉ lấy 1 bản ghi và có thể
        // không phải bản ghi bạn đang nhìn thấy trên trang /payments).
        $dupOrderIds = Payment::select('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_id');

        if ($dupOrderIds->isNotEmpty()) {
            $this->warn('⚠️  Các đơn sau có NHIỀU HƠN 1 bản ghi payment (cần kiểm tra tay):');
            foreach ($dupOrderIds as $oid) {
                $order = Order::find($oid);
                $this->line('   - Order #' . $oid . ' (' . ($order->tracking_number ?? '?') . ') có ' .
                    Payment::where('order_id', $oid)->count() . ' bản ghi payment: ' .
                    Payment::where('order_id', $oid)->pluck('id')->join(', '));
            }
            $this->newLine();
        }

        $orders = Order::with('payment')
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->get();

        $changed = 0;
        $rows = [];

        foreach ($orders as $order) {
            $payment = $order->payment;
            if (!$payment) {
                $rows[] = [$order->tracking_number, '(không có payment)', $order->payment_status, '—', '—'];
                continue;
            }

            $targetStatus = $order->payment_status === 'paid' ? 'success' : 'refunded';

            if ($payment->status === $targetStatus) {
                continue; // đã khớp, bỏ qua
            }

            $rows[] = [
                $order->tracking_number,
                '#' . $payment->id,
                $order->payment_status,
                $payment->status . ' → ' . $targetStatus,
                $dryRun ? 'sẽ cập nhật' : 'đã cập nhật',
            ];

            if (!$dryRun) {
                $payment->update([
                    'status'         => $targetStatus,
                    'transaction_id' => $targetStatus === 'success'
                        ? ($payment->transaction_id ?: (strtoupper($order->payment_method) . '-' . $order->tracking_number))
                        : $payment->transaction_id,
                    'paid_at'        => $targetStatus === 'success' ? ($payment->paid_at ?: now()) : $payment->paid_at,
                ]);
            }

            $changed++;
        }

        if (empty($rows)) {
            $this->info('✅ Không có đơn nào bị lệch — tất cả payments.status đã khớp orders.payment_status.');
            return self::SUCCESS;
        }

        $this->table(
            ['Mã đơn', 'Payment', 'orders.payment_status', 'payments.status', 'Kết quả'],
            $rows
        );

        if ($dryRun) {
            $this->comment('Đây là chế độ xem trước (--dry-run), chưa có gì được ghi vào DB. Chạy lại không kèm --dry-run để áp dụng.');
        } else {
            $this->info("✅ Đã đồng bộ {$changed} bản ghi payment.");
        }

        return self::SUCCESS;
    }
}