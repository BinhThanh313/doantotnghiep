<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'customer_name', 'customer_email',
        'customer_phone', 'address', 'province',
        'total_amount', 'shipping_fee', 'discount_amount',
        'status', 'payment_status', 'payment_method',
        'notes', 'tracking_number',
        'invoice_number',
        'return_reason', 'refund_amount', 'return_status',
    ];

    protected $casts = [
        'total_amount'    => 'float',
        'shipping_fee'    => 'float',
        'discount_amount' => 'float',
        'refund_amount'   => 'float',
    ];

    // ==================== RELATIONSHIPS ====================

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'order_vouchers')
                    ->withPivot('discount_amount')
                    ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // ==================== SCOPES ====================

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', 'shipped');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'unpaid');
    }

    /**
     * Scope lọc trong khoảng thời gian
     */
    public function scopeDateBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('created_at', '>=', $from)
                     ->whereDate('created_at', '<=', $to);
    }

    /**
     * Scope tìm kiếm tổng hợp
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('customer_name', 'like', "%{$keyword}%")
              ->orWhere('customer_phone', 'like', "%{$keyword}%")
              ->orWhere('customer_email', 'like', "%{$keyword}%")
              ->orWhere('tracking_number', 'like', "%{$keyword}%");
        });
    }

    // ==================== ACCESSORS ====================

    public function getGrandTotalAttribute(): float
    {
        return ($this->total_amount ?? 0)
             + ($this->shipping_fee ?? 0)
             - ($this->discount_amount ?? 0);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'       => 'Chờ xử lý',
            'processing'    => 'Đang chuẩn bị',
            'ready_to_ship' => 'Sẵn sàng giao',
            'shipped'       => 'Đang vận chuyển',
            'delivered'     => 'Đã giao hàng',
            'completed'     => 'Hoàn thành',
            'cancelled'     => 'Đã hủy',
            default         => $this->status,
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'unpaid'   => 'Chưa thanh toán',
            'paid'     => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
            default    => $this->payment_status,
        };
    }

    // ==================== HELPERS ====================

    /**
     * Tạo tracking number duy nhất dạng ORD-XXXXXXXX
     */
    public static function generateTrackingNumber(): string
    {
        do {
            $number = 'ORD-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (self::where('tracking_number', $number)->exists());

        return $number;
    }

    /**
     * Tạo invoice number duy nhất dạng INV-YYYYMMDD-XXXXX
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $last   = self::where('invoice_number', 'like', $prefix . '%')
                      ->orderByDesc('invoice_number')
                      ->value('invoice_number');
        $seq    = $last ? ((int) substr($last, -5) + 1) : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Tính tổng tiền tự động từ items
     */
    public function recalculateTotal(): void
    {
        $itemTotal = $this->items()->selectRaw('SUM(price * quantity) as total')->value('total') ?? 0;
        $this->update(['total_amount' => $itemTotal]);
    }

    /**
     * Kiểm tra order có thể hủy không
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Kiểm tra order có thể hoàn tiền không
     */
    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['delivered', 'completed'])
            && $this->payment_status === 'paid';
    }

    /**
     * Kiểm tra order có thể chuyển sang status mới theo workflow không
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $workflow = [
            'pending'       => ['processing', 'cancelled'],
            'processing'    => ['ready_to_ship', 'cancelled'],
            'ready_to_ship' => ['shipped', 'cancelled'],
            'shipped'       => ['delivered'],
            'delivered'     => ['completed', 'cancelled'],
            'completed'     => [],
            'cancelled'     => [],
        ];

        return in_array($newStatus, $workflow[$this->status] ?? []);
    }

    /**
     * Validation rules cho khi tạo order
     */
    public static function rules(): array
    {
        return [
            'customer_name'  => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'address'        => 'required|string|max:500',
            'province'       => 'required|string|max:100',
            'payment_method' => 'required|in:cod,bank',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1|max:999',
        ];
    }
}