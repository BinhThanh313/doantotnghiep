<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Services\BankTransferService;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public array $bankInfo = [];

    public function __construct(
        public Order $order,
    ) {
        if (strtolower($order->payment_method) === 'bank') {
            $this->bankInfo = app(BankTransferService::class)->getTransferInfo($order);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Xác nhận đơn hàng #' . $this->order->tracking_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.confirmation',
            with: [
                'order'          => $this->order,
                'grandTotal'     => $this->order->total_amount
                                  + ($this->order->shipping_fee ?? 0)
                                  - ($this->order->discount_amount ?? 0),
                'bankInfo'       => $this->bankInfo,
                'isBankTransfer' => !empty($this->bankInfo),
            ],
        );
    }

    public function attachments(): array { return []; }
}