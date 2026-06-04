<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order  $order,
        public string $newStatus,
        public string $statusMessage,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->newStatus) {
            'processing'    => '🔄 Đơn hàng đang được chuẩn bị',
            'ready_to_ship' => '📦 Đơn hàng sẵn sàng giao',
            'shipped'       => '🚚 Đơn hàng đang trên đường',
            'delivered'     => '✅ Đơn hàng đã giao thành công',
            'completed'     => '🎉 Đơn hàng hoàn thành',
            'cancelled'     => '❌ Đơn hàng bị hủy',
            default         => 'Cập nhật đơn hàng #' . $this->order->tracking_number,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status-updated',
            with: [
                'order'         => $this->order,
                'newStatus'     => $this->newStatus,
                'statusMessage' => $this->statusMessage,
                'grandTotal'    => $this->order->grand_total,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}