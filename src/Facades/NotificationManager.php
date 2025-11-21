<?php

namespace Csouza\NotificationManagement\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void send($notifiable, string $notificationType, \Illuminate\Notifications\Notification|string $notification, array $data = [])
 * @method static void sendVia($notifiable, array $channels, \Illuminate\Notifications\Notification|string $notification, array $data = [])
 * @method static array getEnabledChannelsForUser($notifiable, string $notificationType)
 * @method static bool userWantsNotificationVia($notifiable, string $notificationType, string $channel)
 * @method static void enableChannel($notifiable, string $notificationType, string $channel)
 * @method static void disableChannel($notifiable, string $notificationType, string $channel)
 * @method static \Illuminate\Database\Eloquent\Collection getUserPreferences($notifiable)
 * @method static void setUserPreferences($notifiable, array $preferences)
 * @method static mixed getNotificationHistory($notifiable, ?string $notificationType = null, int $limit = 50)
 *
 * @see \Csouza\NotificationManagement\Managers\NotificationManager
 */
class NotificationManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Csouza\NotificationManagement\Managers\NotificationManager::class;
    }
}
