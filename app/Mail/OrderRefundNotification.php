<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderRefundNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order  $order,
        public string $reason,
        public float  $refundAmount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💰 Hoàn tiền đơn hàng #' . $this->order->tracking_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.refund-notification',
            with: [
                'order'        => $this->order,
                'reason'       => $this->reason,
                'refundAmount' => $this->refundAmount,
            ],
        );
    }

    public function attachments(): array { return []; }
}