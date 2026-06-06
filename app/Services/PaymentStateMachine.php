<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment Lifecycle State Machine
 *
 * States:   pending → processing → success → refunding → refunded
 *                  ↘ failed
 *
 * Transitions:
 *   pending    → processing  (gateway redirect / IPN received)
 *   pending    → failed      (timeout / user cancel)
 *   processing → success     (IPN confirmed)
 *   processing → failed      (gateway error)
 *   success    → refunding   (admin initiates refund)
 *   refunding  → refunded    (gateway confirms refund)
 *   refunding  → success     (refund gateway error — rollback)
 */
class PaymentStateMachine
{
    // Valid transitions map
    private const TRANSITIONS = [
        'pending'    => ['processing', 'failed'],
        'processing' => ['success', 'failed'],
        'success'    => ['refunding'],
        'refunding'  => ['refunded', 'success'],   // success = rollback nếu refund thất bại
        'refunded'   => [],
        'failed'     => ['pending'],               // retry
    ];

    public function __construct(private Payment $payment) {}

    // ─────────────────────────────────────────────────────────
    // Public transition methods
    // ─────────────────────────────────────────────────────────

    /** Gateway redirect đã gửi / IPN đầu tiên nhận được */
    public function transitionToProcessing(array $meta = []): bool
    {
        return $this->transition('processing', $meta);
    }

    /** IPN xác nhận thanh toán thành công */
    public function transitionToSuccess(string $transactionId, array $gatewayRaw = []): bool
    {
        return $this->transition('success', [
            'transaction_id'    => $transactionId,
            'gateway_response'  => $gatewayRaw,
            'paid_at'           => now()->toISOString(),
        ], function (Payment $p) use ($transactionId, $gatewayRaw) {
            $p->transaction_id   = $transactionId;
            $p->gateway_response = $gatewayRaw;
            $p->paid_at          = now();
            $p->save();

            // Sync order payment_status
            $p->order->update(['payment_status' => 'paid']);
        });
    }

    /** Thanh toán thất bại (timeout / cancel / lỗi gateway) */
    public function transitionToFailed(string $reason = '', array $gatewayRaw = []): bool
    {
        return $this->transition('failed', [
            'fail_reason'      => $reason,
            'gateway_response' => $gatewayRaw,
        ], function (Payment $p) use ($gatewayRaw) {
            if ($gatewayRaw) {
                $p->gateway_response = array_merge($p->gateway_response ?? [], $gatewayRaw);
                $p->save();
            }
        });
    }

    /** Admin hoặc hệ thống khởi tạo hoàn tiền */
    public function transitionToRefunding(string $reason = '', float $refundAmount = 0): bool
    {
        return $this->transition('refunding', [
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

    /** Gateway xác nhận hoàn tiền hoàn tất */
    public function transitionToRefunded(string $refundTxnId = '', array $gatewayRaw = []): bool
    {
        return $this->transition('refunded', [
            'refund_transaction_id' => $refundTxnId,
            'gateway_response'      => $gatewayRaw,
        ], function (Payment $p) use ($refundTxnId, $gatewayRaw) {
            $p->gateway_response = array_merge($p->gateway_response ?? [], array_merge(
                $gatewayRaw,
                ['refund_transaction_id' => $refundTxnId, 'refunded_at' => now()->toISOString()]
            ));
            $p->save();

            // Sync order
            $p->order->update(['payment_status' => 'refunded']);
        });
    }

    /** Retry sau khi failed */
    public function retryFromFailed(): bool
    {
        return $this->transition('pending', ['retry_at' => now()->toISOString()]);
    }

    // ─────────────────────────────────────────────────────────
    // Query helpers
    // ─────────────────────────────────────────────────────────

    public function canTransitionTo(string $newState): bool
    {
        return in_array($newState, self::TRANSITIONS[$this->payment->status] ?? []);
    }

    public function currentState(): string
    {
        return $this->payment->status;
    }

    public function isTerminal(): bool
    {
        return in_array($this->payment->status, ['success', 'refunded', 'failed']);
    }

    public function isPaid(): bool
    {
        return $this->payment->status === 'success';
    }

    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->payment->status] ?? [];
    }

    // ─────────────────────────────────────────────────────────
    // Core transition engine
    // ─────────────────────────────────────────────────────────

    private function transition(string $newState, array $meta = [], ?callable $sideEffect = null): bool
    {
        if (!$this->canTransitionTo($newState)) {
            Log::warning('PaymentStateMachine: invalid transition', [
                'payment_id'   => $this->payment->id,
                'from'         => $this->payment->status,
                'to'           => $newState,
            ]);
            return false;
        }

        try {
            DB::transaction(function () use ($newState, $meta, $sideEffect) {
                $oldState = $this->payment->status;

                $this->payment->status = $newState;
                $this->payment->save();

                // Run any side effects (update related fields, sync order, etc.)
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
}