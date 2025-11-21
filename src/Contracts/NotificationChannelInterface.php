<?php

namespace Csouza\NotificationManagement\Contracts;

interface NotificationChannelInterface
{
    /**
     * Envia a notificação através do canal
     *
     * @param  mixed  $notifiable  O objeto que receberá a notificação (geralmente User)
     * @param  string  $notificationType  Tipo da notificação (ex: 'order.shipped')
     * @param  array  $data  Dados da notificação
     * @return bool Retorna true se enviado com sucesso
     */
    public function send($notifiable, string $notificationType, array $data): bool;

    /**
     * Retorna o nome único do canal
     */
    public function getName(): string;

    /**
     * Valida se o canal pode ser usado para enviar a notificação
     *
     * @param  mixed  $notifiable
     */
    public function canSend($notifiable): bool;
}
