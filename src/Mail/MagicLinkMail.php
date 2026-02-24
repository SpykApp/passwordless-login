<?php

namespace SpykApp\PasswordlessLogin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $url,
        public readonly int $expiryMinutes,
        public readonly string $userName = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('passwordless-login::messages.email_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'passwordless-login::emails.magic-link',
            with: [
                'url' => $this->url,
                'expiryMinutes' => $this->expiryMinutes,
                'userName' => $this->userName,
            ],
        );
    }
}
