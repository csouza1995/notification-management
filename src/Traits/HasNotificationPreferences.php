<?php

namespace Csouza\NotificationManagement\Traits;

use Csouza\NotificationManagement\Managers\NotificationManager;
use Csouza\NotificationManagement\Models\UserNotificationPreference;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasNotificationPreferences
{
    /**
     * Boot the trait and initialize default preferences
     */
    public static function bootHasNotificationPreferences(): void
    {
        static::created(function ($user) {
            $user->initializeDefaultNotificationPreferences();
        });
    }

    /**
     * Initialize default notification preferences for new users
     */
    public function initializeDefaultNotificationPreferences(): void
    {
        $enabledNotifications = config('notification-management.defaults.enabled_notifications', []);

        foreach ($enabledNotifications as $notificationType => $channels) {
            // If '*' is specified, use all enabled channels
            if ($channels === ['*'] || (is_array($channels) && in_array('*', $channels))) {
                $channels = config('notification-management.defaults.enabled_channels', ['mail', 'database']);
            }

            // Create preferences for each channel
            foreach ($channels as $channel) {
                UserNotificationPreference::firstOrCreate([
                    'user_id' => $this->getKey(),
                    'notification_type' => $notificationType,
                    'channel_name' => $channel,
                ], [
                    'is_enabled' => true,
                ]);
            }
        }
    }

    /**
     * Relationship to user notification preferences
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class, 'user_id');
    }

    /**
     * Enable a notification channel for a specific notification type
     */
    public function enableNotificationChannel(string $notificationType, string $channel): void
    {
        app(NotificationManager::class)->enableChannel($this, $notificationType, $channel);
    }

    /**
     * Disable a notification channel for a specific notification type
     */
    public function disableNotificationChannel(string $notificationType, string $channel): void
    {
        app(NotificationManager::class)->disableChannel($this, $notificationType, $channel);
    }

    /**
     * Check if user wants to receive notification via specific channel
     */
    public function wantsNotificationVia(string $notificationType, string $channel): bool
    {
        return app(NotificationManager::class)->userWantsNotificationVia($this, $notificationType, $channel);
    }

    /**
     * Get all active channels for a notification type
     */
    public function getActiveChannelsFor(string $notificationType): array
    {
        return app(NotificationManager::class)->getEnabledChannelsForUser($this, $notificationType);
    }

    /**
     * Get all notification preferences for this user
     */
    public function getNotificationPreferences(): \Illuminate\Database\Eloquent\Collection
    {
        return app(NotificationManager::class)->getUserPreferences($this);
    }

    /**
     * Set bulk notification preferences
     *
     * Example:
     * $user->setNotificationPreferences([
     *     'order.shipped' => ['mail' => true, 'sms' => false],
     *     'order.delivered' => ['mail' => true, 'database' => true],
     * ]);
     */
    public function setNotificationPreferences(array $preferences): void
    {
        app(NotificationManager::class)->setUserPreferences($this, $preferences);
    }

    /**
     * Get notification history for this user
     */
    public function getNotificationHistory(?string $notificationType = null, int $limit = 50)
    {
        return app(NotificationManager::class)->getNotificationHistory($this, $notificationType, $limit);
    }

    /**
     * Check if user has a preference set for a notification type and channel
     */
    public function hasPreferenceFor(string $notificationType, string $channel): bool
    {
        return $this->notificationPreferences()
            ->where('notification_type', $notificationType)
            ->where('channel_name', $channel)
            ->exists();
    }

    /**
     * Get preference for a specific notification type and channel
     */
    public function getPreferenceFor(string $notificationType, string $channel): ?UserNotificationPreference
    {
        return $this->notificationPreferences()
            ->where('notification_type', $notificationType)
            ->where('channel_name', $channel)
            ->first();
    }

    /**
     * Enable all default channels for a notification type
     */
    public function enableDefaultChannelsFor(string $notificationType): void
    {
        $defaultChannels = config('notification-management.defaults.enabled_channels', []);

        foreach ($defaultChannels as $channel) {
            $this->enableNotificationChannel($notificationType, $channel);
        }
    }

    /**
     * Disable all channels for a notification type
     */
    public function disableAllChannelsFor(string $notificationType): void
    {
        $this->notificationPreferences()
            ->where('notification_type', $notificationType)
            ->update(['is_enabled' => false]);
    }
}
