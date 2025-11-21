<?php

namespace Csouza\NotificationManagement\Services;

use Csouza\NotificationManagement\Managers\NotificationManager;
use Illuminate\Support\Collection;

class EventNotificationMapper
{
    public function __construct(
        protected NotificationManager $notificationManager
    ) {}

    /**
     * Handle an event and send notification based on configuration
     */
    public function handle(object $event, array $config): void
    {
        // Check if mapping is enabled
        if (isset($config['enabled']) && $config['enabled'] === false) {
            return;
        }

        // Check condition if present
        if (isset($config['condition']) && is_callable($config['condition'])) {
            if (! $config['condition']($event)) {
                return; // Condition not met, skip notification
            }
        }

        // Extract notifiable(s)
        $notifiables = $this->extractNotifiable($event, $config['notifiable'] ?? null);

        if (empty($notifiables)) {
            return; // No notifiable found
        }

        // Extract additional data
        $data = $this->extractData($event, $config['data'] ?? []);

        // Get notification type
        $notificationType = $config['notification_type'] ?? null;

        if (! $notificationType) {
            throw new \InvalidArgumentException('notification_type is required in event notification mapping.');
        }

        // Send to each notifiable
        foreach ($this->ensureCollection($notifiables) as $notifiable) {
            if ($notifiable && method_exists($notifiable, 'getActiveChannelsFor')) {
                $this->notificationManager->sendByType($notifiable, $notificationType, $data);
            }
        }
    }

    /**
     * Extract notifiable from event based on configuration
     */
    protected function extractNotifiable(object $event, mixed $notifiableConfig): mixed
    {
        if ($notifiableConfig === null) {
            return null;
        }

        // Closure: custom extraction logic
        if (is_callable($notifiableConfig)) {
            return $notifiableConfig($event);
        }

        // String: property name or dot notation
        if (is_string($notifiableConfig)) {
            return $this->extractFromDotNotation($event, $notifiableConfig);
        }

        return null;
    }

    /**
     * Extract value using dot notation
     *
     * Supports: 'user', 'order.user', 'comment.post.author'
     */
    protected function extractFromDotNotation(object $event, string $path): mixed
    {
        $segments = explode('.', $path);
        $value = $event;

        foreach ($segments as $segment) {
            if (is_object($value) && property_exists($value, $segment)) {
                $value = $value->{$segment};
            } elseif (is_object($value) && method_exists($value, $segment)) {
                $value = $value->{$segment}();
            } else {
                return null; // Path not found
            }
        }

        return $value;
    }

    /**
     * Extract additional data to pass to notification
     */
    protected function extractData(object $event, mixed $dataConfig): array
    {
        if (empty($dataConfig)) {
            return [];
        }

        // Closure: custom data extraction
        if (is_callable($dataConfig)) {
            $result = $dataConfig($event);

            return is_array($result) ? $result : [];
        }

        // Array: static data
        if (is_array($dataConfig)) {
            return $dataConfig;
        }

        return [];
    }

    /**
     * Ensure value is a collection (supports single or multiple notifiables)
     */
    protected function ensureCollection(mixed $value): Collection
    {
        if ($value instanceof Collection) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value);
        }

        // Single notifiable
        return collect([$value]);
    }
}
