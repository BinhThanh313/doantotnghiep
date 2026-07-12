<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📩 Tin nhắn liên hệ mới từ ' . $this->contactMessage->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.received',
            with: [
                'contactMessage' => $this->contactMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
