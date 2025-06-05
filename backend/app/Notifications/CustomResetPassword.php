<?php

namespace App\Notifications;


use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class CustomResetPassword extends ResetPassword
{
    /**
     * Build the reset password email.
     */
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = 'https://pokedeals.ch/reset-password';
        $url = "{$frontendUrl}/{$this->token}?email=" . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('Click the button below to reset your password:')
            ->action('Reset Password', $url)
            ->line('If you didn’t request a password reset, you can safely ignore this email.');
    }
}
