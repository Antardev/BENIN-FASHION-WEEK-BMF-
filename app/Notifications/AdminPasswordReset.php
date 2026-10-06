<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminPasswordReset extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe administrateur')
            ->greeting('Bonjour,')
            ->line('Vous avez demandé la réinitialisation du mot de passe de votre compte administrateur.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line('Ce lien expirera dans '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet e-mail.');
    }
}
