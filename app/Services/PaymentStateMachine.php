<?php
// app/Services/PaymentStateMachine.php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stateless Payment State Machine.
 *
 * Dùng theo 2 cách:
 *   // 1. Static (cho controller DI, listing):
 *   PaymentStateMachine::label($status)
 *   PaymentStateMachine::color($status)
 *   PaymentStateMachine::availableTransitions($payment)   ← NEW static
 *   PaymentStateMachine::canDo($payment, 'success')       ← NEW static
 *
 *   // 2. Instance per-payment (cho business logic):
 *   PaymentStateMachine::for($payment)->transitionToSuccess(...)
 */
class PaymentStateMachine
{
    private const TRANSITIONS = [
        'pending'    => ['processing', 'failed'],
        'processing' => ['success', 'failed'],
        'success'    => ['refunding'],
        'refunding'  => ['refunded', 'success'],
        'refunded'   => [],
        'failed'     => ['pending'],
    ];

    private const LABELS = [
        'pending'    => 'Chờ thanh toán',
        'processing' => 'Đang xử lý',
        'success'    => 'Thành công',
        'refunding'  => 'Đang hoàn tiền',
        'refunded'   => 'Đã hoàn tiền',
        'failed'     => 'Thất bại',
    ];

    private const COLORS = [
        'pending'    => 'yellow',
        'processing' => 'blue',
        'success'    => 'green',
        'refunding'  => 'orange',
        'refunded'   => 'purple',
        'failed'     => 'red',
    ];

    // Constructor private — chỉ dùng qua ::for()
    private function __construct(private Payment $payment) {}

    // ─────────────────────────────────────────────────────────
    // Static helpers — dùng được khi inject qua DI (không cần Payment)
    // ─────────────────────────────────────────────────────────

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function color(string $status): string
    {
        return self::COLORS[$status] ?? 'gray';
    }

    /**
     * Trả về danh sách transition hợp lệ từ trạng thái hiện tại của $payment.
     * Dùng trong controller listing/show mà không cần khởi tạo instance.
     */
    public static function availableTransitions(Payment $payment): array
    {
        return self::TRANSITIONS[$payment->status] ?? [];
    }

    public static function canDo(Payment $payment, string $toState): bool
    {
        return in_array($toState, self::availableTransitions($payment));
    }

    // ─────────────────────────────────────────────────────────
    // Factory
    // ─────────────────────────────────────────────────────────

    public static function for(Payment $payment): static
    {
        return new static($payment);
    }

    public static function forOrder(Order $order): static
    {
        $payment = $order->payment ?? Payment::where('order_id', $order->id)->firstOrFail();
        return new static($payment);
    }

    // ─────────────────────────────────────────────────────────
    // Instance methods — gọi qua ::for($payment)->...
    // ─────────────────────────────────────────────────────────

    public function canTransitionTo(string $newState): bool
    {
        return in_array($newState, self::TRANSITIONS[$this->payment->status] ?? []);
    }

    public function isTerminal(): bool
    {
        return in_array($this->payment->status, ['success', 'refunded', 'failed']);
    }

    public function isPaid(): bool
    {
        return $this->payment->status === 'success';
    }

    public function transitionToProcessing(array $meta = []): bool
    {
        return $this->doTransition('processing', $meta);
    }

    public function transitionToSuccess(string $transactionId, array $gatewayRaw = []): bool
    {
        return $this->doTransition('success', [
            'transaction_id'   => $transactionId,
            'gateway_response' => $gatewayRaw,
            'paid_at'          => now()->toISOString(),
        ], function (Payment $p) use ($transactionId, $gatewayRaw) {
            $p->transaction_id   = $transactionId;
            $p->gateway_response = $gatewayRaw;
            $p->paid_at          = now();
            $p->save();
            $p->order->update(['payment_status' => 'paid']);
        });
    }

    public function transitionToFailed(string $reason = '', array $gatewayRaw = []): bool
    {
        return $this->doTransition('failed', [
            'fail_reason'      => $reason,
            'gateway_response' => $gatewayRaw,
        ], function (Payment $p) use ($gatewayRaw) {
            if ($gatewayRaw) {
                $p->gateway_response = array_merge($p->gateway_response ?? [], $gatewayRaw);
                $p->save();
            }
        });
    }

    public function transitionToRefunding(string $reason = '', float $refundAmount = 0): bool
    {
        return $this->doTransition('refunding', [
            'refund_reason' => $reason,
            'refund_amount' => $refundAmount ?: $this->payment->amount,
        ], function (Payment $p) use ($reason, $refundAmount) {
            $p->gateway_response = array_merge($p->gateway_response ?? [], [
                'refund_initiated_at' => now()->toISOString(),
                'refund_reason'       => $reason,
                'refund_amount'       => $refundAmount ?: $p->amount,
            ]);
            $p->save();
        });
    }

    public function transitionToRefunded(string $refundTxnId = '', array $gatewayRaw = []): bool
    {
        return $this->doTransition('refunded', [
            'refund_transaction_id' => $refundTxnId,
            'gateway_response'      => $gatewayRaw,
        ], function (Payment $p) use ($refundTxnId, $gatewayRaw) {
            $p->gateway_response = array_merge($p->gateway_response ?? [], array_merge(
                $gatewayRaw,
                ['refund_transaction_id' => $refundTxnId, 'refunded_at' => now()->toISOString()]
            ));
            $p->save();
            $p->order->update(['payment_status' => 'refunded']);

            $reason = $p->gateway_response['refund_reason'] ?? 'Hoàn tiền qua cổng thanh toán';
            $refundAmount = $p->gateway_response['refund_amount'] ?? $p->amount;

            if ($p->order->customer_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($p->order->customer_email)
                        ->send(new \App\Mail\OrderRefundNotification($p->order, $reason, (float) $refundAmount));
                } catch (\Exception $e) {
                    Log::error('Refund email failed in PaymentStateMachine: ' . $e->getMessage());
                }
            }

            if ($p->order->user_id) {
                \App\Models\AppNotification::send(
                    $p->order->user_id,
                    'order_refunded',
                    'Hoàn tiền đơn hàng #' . ($p->order->tracking_number ?? $p->order->id),
                    'Đơn hàng của bạn đã được hoàn tiền ' . number_format($refundAmount) . 'đ',
                    $p->order->id,
                    'order'
                );
            }
        });
    }

    public function retryFromFailed(): bool
    {
        return $this->doTransition('pending', ['retry_at' => now()->toISOString()]);
    }

    /**
     * Generic transition — dùng trong AdminPaymentController::transition()
     * để admin ép trạng thái thủ công.
     *
     * @throws \InvalidArgumentException nếu transition không hợp lệ
     */
    public function transition(Payment $payment, string $toState, array $meta = []): Payment
    {
        if (!self::canDo($payment, $toState)) {
            throw new \InvalidArgumentException(
                "Không thể chuyển từ [{$payment->status}] sang [{$toState}]"
            );
        }

        // Bind payment rồi delegate xuống doTransition
        $this->payment = $payment;
        $ok = $this->doTransition($toState, $meta);

        if (!$ok) {
            throw new \InvalidArgumentException("Transition thất bại — xem log để biết chi tiết.");
        }

        return $this->payment->fresh();
    }

    // ─────────────────────────────────────────────────────────
    // Core engine (private)
    // ─────────────────────────────────────────────────────────

    private function doTransition(string $newState, array $meta = [], ?callable $sideEffect = null): bool
    {
        if (!$this->canTransitionTo($newState)) {
            Log::warning('PaymentStateMachine: invalid transition', [
                'payment_id' => $this->payment->id,
                'from'       => $this->payment->status,
                'to'         => $newState,
            ]);
            return false;
        }

        try {
            DB::transaction(function () use ($newState, $meta, $sideEffect) {
                $oldState = $this->payment->status;
                $this->payment->status = $newState;
                $this->payment->save();

                if ($sideEffect) {
                    $sideEffect($this->payment);
                }

                Log::info('PaymentStateMachine: transition', [
                    'payment_id' => $this->payment->id,
                    'from'       => $oldState,
                    'to'         => $newState,
                    'meta'       => $meta,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('PaymentStateMachine: transition failed', [
                'payment_id' => $this->payment->id,
                'to'         => $newState,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }
}