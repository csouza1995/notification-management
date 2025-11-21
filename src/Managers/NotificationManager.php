<?php

namespace Csouza\NotificationManagement\Managers;

use Csouza\NotificationManagement\Models\NotificationLog;
use Csouza\NotificationManagement\Models\UserNotificationPreference;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationManager
{
    public function __construct(
        protected ChannelRegistry $channelRegistry
    ) {}

    /**
     * Send a notification respecting user preferences
     *
     * @param  mixed  $notifiable  User or notifiable entity
     * @param  string  $notificationType  Type identifier (e.g., 'order.shipped')
     * @param  Notification|string  $notification  Laravel Notification instance or class name
     * @param  array  $data  Additional data for the notification
     */
    public function send($notifiable, string $notificationType, Notification|string $notification, array $data = []): void
    {
        // Get user's enabled channels for this notification type
        $enabledChannels = $this->getEnabledChannelsForUser($notifiable, $notificationType);

        if (empty($enabledChannels)) {
            return;
        }

        // Create notification instance if class name was passed
        if (is_string($notification)) {
            $notification = new $notification($data);
        }

        // Send via Laravel's notification system
        try {
            $notifiable->notify($notification);

            // Log successful send if enabled
            if (config('notification-management.defaults.log_notifications', true)) {
                $this->logNotification($notifiable, $notificationType, $enabledChannels, $data, 'sent');
            }
        } catch (\Exception $e) {
            // Log failure
            if (config('notification-management.defaults.log_notifications', true)) {
                $this->logNotification($notifiable, $notificationType, $enabledChannels, $data, 'failed', $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Send notification using type from config
     *
     * @param  mixed  $notifiable  User or notifiable entity
     * @param  string  $notificationType  Type identifier (e.g., 'user.logged')
     * @param  array  $data  Data to pass to the notification
     */
    public function sendByType($notifiable, string $notificationType, array $data = []): void
    {
        // Get notification class from config
        $notificationClass = config("notification-management.notification_types.{$notificationType}");

        if (! $notificationClass) {
            throw new \InvalidArgumentException("Notification type '{$notificationType}' is not registered in config.");
        }

        if (! class_exists($notificationClass)) {
            throw new \InvalidArgumentException("Notification type '{$notificationType}' is mapped to '{$notificationClass}' which is not a valid notification class.");
        }

        // Send using the regular send method
        $this->send($notifiable, $notificationType, $notificationClass, $data);
    }

    /**
     * Send notification to multiple channels explicitly
     */
    public function sendVia($notifiable, array $channels, Notification|string $notification, array $data = []): void
    {
        if (is_string($notification)) {
            $notification = new $notification($data);
        }

        NotificationFacade::send($notifiable, $notification);
    }

    /**
     * Get enabled channels for a user and notification type
     */
    public function getEnabledChannelsForUser($notifiable, string $notificationType): array
    {
        $userId = $notifiable->getKey();

        // Get user preferences
        $preferences = UserNotificationPreference::query()
            ->where('user_id', $userId)
            ->where('notification_type', $notificationType)
            ->where('is_enabled', true)
            ->pluck('channel_name')
            ->toArray();

        // If no preferences set, use defaults
        if (empty($preferences)) {
            return $this->getDefaultChannelsForNotificationType($notificationType);
        }

        return $preferences;
    }

    /**
     * Get default channels for a specific notification type
     */
    protected function getDefaultChannelsForNotificationType(string $notificationType): array
    {
        $enabledNotifications = config('notification-management.defaults.enabled_notifications', []);

        // Check if this notification type has specific defaults
        if (isset($enabledNotifications[$notificationType])) {
            $channels = $enabledNotifications[$notificationType];

            // If '*' is specified, use all enabled channels
            if ($channels === ['*'] || (is_array($channels) && in_array('*', $channels))) {
                return config('notification-management.defaults.enabled_channels', ['mail', 'database']);
            }

            return $channels;
        }

        // If notification type not in enabled_notifications, return empty array (disabled by default)
        return [];
    }

    /**
     * Check if user wants to receive notification via specific channel
     */
    public function userWantsNotificationVia($notifiable, string $notificationType, string $channel): bool
    {
        $userId = $notifiable->getKey();

        $preference = UserNotificationPreference::query()
            ->where('user_id', $userId)
            ->where('notification_type', $notificationType)
            ->where('channel_name', $channel)
            ->first();

        if (! $preference) {
            // Check if channel is in defaults
            return in_array($channel, config('notification-management.defaults.enabled_channels', []));
        }

        return $preference->is_enabled;
    }

    /**
     * Enable a channel for user and notification type
     */
    public function enableChannel($notifiable, string $notificationType, string $channel): void
    {
        UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $notifiable->getKey(),
                'notification_type' => $notificationType,
                'channel_name' => $channel,
            ],
            [
                'is_enabled' => true,
            ]
        );
    }

    /**
     * Disable a channel for user and notification type
     */
    public function disableChannel($notifiable, string $notificationType, string $channel): void
    {
        UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $notifiable->getKey(),
                'notification_type' => $notificationType,
                'channel_name' => $channel,
            ],
            [
                'is_enabled' => false,
            ]
        );
    }

    /**
     * Get all preferences for a user
     */
    public function getUserPreferences($notifiable): \Illuminate\Database\Eloquent\Collection
    {
        return UserNotificationPreference::where('user_id', $notifiable->getKey())->get();
    }

    /**
     * Set bulk preferences for a user
     */
    public function setUserPreferences($notifiable, array $preferences): void
    {
        foreach ($preferences as $notificationType => $channels) {
            foreach ($channels as $channel => $enabled) {
                if ($enabled) {
                    $this->enableChannel($notifiable, $notificationType, $channel);
                } else {
                    $this->disableChannel($notifiable, $notificationType, $channel);
                }
            }
        }
    }

    /**
     * Log notification send attempt
     */
    protected function logNotification(
        $notifiable,
        string $notificationType,
        array $channels,
        array $data,
        string $status = 'sent',
        ?string $errorMessage = null
    ): void {
        foreach ($channels as $channel) {
            NotificationLog::create([
                'user_id' => $notifiable->getKey(),
                'channel_name' => $channel,
                'notification_type' => $notificationType,
                'status' => $status,
                'payload' => $data,
                'error_message' => $errorMessage,
                'sent_at' => $status === 'sent' ? now() : null,
            ]);
        }
    }

    /**
     * Get notification history for a user
     */
    public function getNotificationHistory($notifiable, ?string $notificationType = null, int $limit = 50)
    {
        $query = NotificationLog::where('user_id', $notifiable->getKey())
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($notificationType) {
            $query->where('notification_type', $notificationType);
        }

        return $query->get();
    }
}
