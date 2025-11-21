<?php

namespace Csouza\NotificationManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $channel_name
 * @property string $notification_type
 * @property string $status
 * @property array<string, mixed>|null $payload
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'channel_name',
        'notification_type',
        'status',
        'payload',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scope para buscar logs enviados
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope para buscar logs com falha
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para buscar logs pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para buscar por tipo de notificação
     */
    public function scopeForNotificationType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope para buscar por usuário
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Marca o log como enviado
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Marca o log como falho
     */
    public function markAsFailed(?string $errorMessage = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Verifica se foi enviado com sucesso
     */
    public function wasSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Verifica se falhou
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
