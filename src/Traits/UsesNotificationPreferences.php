<?php

namespace Csouza\NotificationManagement\Traits;

/**
 * Trait for notifications that should respect user preferences
 *
 * Usage in your notification class:
 *
 * use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
 *
 * class OrderShipped extends Notification
 * {
 *     use UsesNotificationPreferences;
 *
 *     protected string $notificationType = 'order.shipped';
 *
 *     // Optional: limit to specific channels (ignores user preferences)
 *     protected array $forceChannels = ['database'];
 *
 *     // Optional: allow only these channels (intersects with user preferences)
 *     protected array $allowedChannels = ['mail', 'database'];
 *
 *     // ... rest of your notification
 * }
 */
trait UsesNotificationPreferences
{
    /**
     * Get the notification's delivery channels based on user preferences.
     *
     * Override this method if you need custom logic.
     */
    public function via(object $notifiable): array
    {
        // If forceChannels is set, use only those (ignores user preferences)
        if (property_exists($this, 'forceChannels') && ! empty($this->forceChannels)) {
            return $this->forceChannels;
        }

        // Check if the notifiable has the HasNotificationPreferences trait
        if (! method_exists($notifiable, 'getActiveChannelsFor')) {
            throw new \RuntimeException(
                'Notifiable entity must use HasNotificationPreferences trait to use UsesNotificationPreferences.'
            );
        }

        // Get the notification type from the property or class name
        $notificationType = $this->getNotificationType();

        // Get user's enabled channels for this notification type
        $userChannels = $notifiable->getActiveChannelsFor($notificationType);

        // If allowedChannels is set, intersect with user preferences
        if (property_exists($this, 'allowedChannels') && ! empty($this->allowedChannels)) {
            return array_values(array_intersect($userChannels, $this->allowedChannels));
        }

        return $userChannels;
    }

    /**
     * Get the notification type identifier.
     *
     * Override this method or set the $notificationType property.
     */
    protected function getNotificationType(): string
    {
        // Check if the property exists
        if (property_exists($this, 'notificationType') && ! empty($this->notificationType)) {
            return $this->notificationType;
        }

        // Fallback: use the class name as type
        // e.g., App\Notifications\OrderShipped -> order.shipped
        return $this->guessNotificationType();
    }

    /**
     * Guess notification type from class name.
     */
    protected function guessNotificationType(): string
    {
        $className = class_basename($this);

        // Remove "Notification" suffix if present
        $className = preg_replace('/Notification$/', '', $className);

        // Convert PascalCase to snake_case
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));

        // Convert snake_case to dot notation (optional, for consistency)
        return str_replace('_', '.', $snakeCase);
    }
}
