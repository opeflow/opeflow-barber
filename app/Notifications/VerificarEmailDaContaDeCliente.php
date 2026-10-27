<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerificarEmailDaContaDeCliente extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('cliente.verificar-email', now()->addMinutes(60), [
            'conta' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);

        return (new MailMessage)
            ->subject('Confirme seu e-mail — Opeflow Barber')
            ->greeting('Olá, '.$notifiable->nome)
            ->line('Confirme seu endereço de e-mail para acessar seus agendamentos.')
            ->action('Confirmar e-mail', $url)
            ->line('Este link expira em 60 minutos. Se você não solicitou o acesso, ignore esta mensagem.');
    }
}
