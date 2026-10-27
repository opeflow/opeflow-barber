<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RedefinirSenhaDaContaDeCliente extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('cliente.redefinir-senha').'#token='.rawurlencode($this->token);

        return (new MailMessage)
            ->subject('Redefinição de senha — Opeflow Barber')
            ->greeting('Olá, '.$notifiable->nome)
            ->line('Recebemos uma solicitação para redefinir a senha do seu acesso ao portal.')
            ->action('Redefinir senha', $url)
            ->line('O link expira em 60 minutos e pode ser usado uma única vez. Se você não solicitou a alteração, ignore esta mensagem.');
    }
}
