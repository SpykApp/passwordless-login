<?php

namespace SpykApp\PasswordlessLogin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected bool $shouldQueue;

    public function __construct(
        public readonly string $url,
        public readonly int $expiryMinutes,
        public readonly array $metadata = [],
    ) {
        $this->shouldQueue = config('passwordless-login.notification.queue', false);

        if ($this->shouldQueue) {
            $this->onQueue(config('passwordless-login.notification.queue_name'));
            $this->onConnection(config('passwordless-login.notification.queue_connection'));
        }
    }

    /**
     * Determine if the notification should be queued.
     */
    public function shouldSend($notifiable, $channel): bool
    {
        return true;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [config('passwordless-login.notification.channel', 'mail')];
    }

    /**
     * Get the mail representation.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('passwordless-login::messages.email_subject'))
            ->greeting(__('passwordless-login::messages.email_greeting'))
            ->line(__('passwordless-login::messages.email_intro'))
            ->action(__('passwordless-login::messages.email_action'), $this->url)
            ->line(__('passwordless-login::messages.email_expiry_notice', [
                'minutes' => $this->expiryMinutes,
            ]))
            ->line(__('passwordless-login::messages.email_outro'))
            ->salutation(__('passwordless-login::messages.email_salutation') . ",\n" . config('app.name'));
    }

    /**
     * Get the array representation (for database channel).
     */
    public function toArray($notifiable): array
    {
        return [
            'url' => $this->url,
            'expiry_minutes' => $this->expiryMinutes,
            'metadata' => $this->metadata,
            'type' => 'magic_link',
        ];
    }

    /**
     * Determine if the notification should be sent.
     */
    public function shouldQueue(): bool
    {
        return $this->shouldQueue;
    }
}
