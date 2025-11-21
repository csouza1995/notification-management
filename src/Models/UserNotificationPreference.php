<?php

namespace Csouza\NotificationManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $channel_name
 * @property string $notification_type
 * @property bool $is_enabled
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'channel_name',
        'notification_type',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Relacionamento com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scope para buscar preferências habilitadas
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
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
     * Verifica se a preferência está habilitada
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Habilita a preferência
     */
    public function enable(): bool
    {
        return $this->update(['is_enabled' => true]);
    }

    /**
     * Desabilita a preferência
     */
    public function disable(): bool
    {
        return $this->update(['is_enabled' => false]);
    }
}
