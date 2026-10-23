<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * E-mail de réinitialisation du mot de passe (en français, même gabarit que les e-mails mail/pro-account-*)
 */
class ResetPasswordNotification extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Farm to Fork')
            ->subject('Réinitialisation de votre mot de passe - Farm to Fork')
            ->view('mail.reset-password', [
                'user' => $notifiable,
                'url' => $this->resetUrl($notifiable),
                'minutes' => $minutes,
            ]);
    }
}
