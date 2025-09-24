<?php

namespace App\Notifications;

use App\Models\PasswordResetToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PasswordResetToken $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(PasswordResetToken $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl();

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->line('For security reasons, we have logged you out of all devices.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the reset URL
     */
    protected function resetUrl(): string
    {
        return config('app.frontend_url', config('app.url')) . '/reset-password?token=' . $this->token->token;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token->token,
            'expires_at' => $this->token->expires_at,
        ];
    }
}
