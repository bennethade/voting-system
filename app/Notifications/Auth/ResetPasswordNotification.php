<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[\SensitiveParameter] public string $token,
        public bool $forStaff = false,
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $route = $this->forStaff ? 'admin.password.reset' : 'password.reset';
        $url = route($route, ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $expires = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset your '.setting('site_name').' password')
            ->markdown('emails.auth.reset-password', ['user' => $notifiable, 'url' => $url, 'expires' => $expires]);
    }
}
