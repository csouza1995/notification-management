<?php

namespace Csouza\NotificationManagement;

use Csouza\NotificationManagement\Managers\ChannelRegistry;
use Csouza\NotificationManagement\Managers\NotificationManager;
use Csouza\NotificationManagement\Services\EventNotificationMapper;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class NotificationManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/notification-management.php',
            'notification-management'
        );

        // Register ChannelRegistry as singleton
        $this->app->singleton(ChannelRegistry::class, function ($app) {
            return new ChannelRegistry;
        });

        // Register NotificationManager as singleton
        $this->app->singleton(NotificationManager::class, function ($app) {
            return new NotificationManager(
                $app->make(ChannelRegistry::class)
            );
        });

        // Register EventNotificationMapper as singleton
        $this->app->singleton(EventNotificationMapper::class, function ($app) {
            return new EventNotificationMapper(
                $app->make(NotificationManager::class)
            );
        });

        // Alias for easier access
        $this->app->alias(NotificationManager::class, 'notification-manager');
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/notification-management.php' => config_path('notification-management.php'),
        ], 'notification-management-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'notification-management-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes (optional, can be disabled in config)
        if (config('notification-management.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        // Register custom channels from config
        $this->registerCustomChannels();

        // Register automatic event-to-notification listeners
        $this->registerEventNotifications();
    }

    protected function registerCustomChannels(): void
    {
        $registry = $this->app->make(ChannelRegistry::class);
        $channels = config('notification-management.channels', []);

        foreach ($channels as $name => $config) {
            if (isset($config['driver']) && ($config['enabled'] ?? true)) {
                $registry->register($name, $config['driver']);
            }
        }
    }

    protected function registerEventNotifications(): void
    {
        $mapper = $this->app->make(EventNotificationMapper::class);
        $eventMappings = config('notification-management.event_notifications', []);

        foreach ($eventMappings as $eventClass => $config) {
            // Skip if explicitly disabled
            if (isset($config['enabled']) && $config['enabled'] === false) {
                continue;
            }

            // Register event listener
            Event::listen($eventClass, function ($event) use ($mapper, $config) {
                try {
                    $mapper->handle($event, $config);
                } catch (\Exception $e) {
                    // Log error but don't break the event flow
                    if (function_exists('report')) {
                        report($e);
                    }
                }
            });
        }
    }
}
