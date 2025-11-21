<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available Notification Channels
    |--------------------------------------------------------------------------
    |
    | Laravel native channels (mail, database, broadcast) are automatically
    | available. Here you can register custom channels.
    |
    | Native Laravel channels:
    | - 'mail': Email notifications (built-in)
    | - 'database': Database notifications (built-in)
    | - 'broadcast': Broadcast notifications (built-in)
    |
    */

    'channels' => [
        // Register your custom channels here:
        //
        // 'push' => [
        //     'driver' => \App\Channels\PushNotificationChannel::class,
        //     'enabled' => true,
        //     'description' => 'Push notifications',
        // ],
        //
        // 'slack' => [
        //     'driver' => \App\Channels\SlackChannel::class,
        //     'enabled' => true,
        //     'description' => 'Slack notifications',
        // ],
        //
        // 'telegram' => [
        //     'driver' => \App\Channels\TelegramChannel::class,
        //     'enabled' => true,
        //     'description' => 'Telegram notifications',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */

    'routes' => [
        // Enable/disable API routes
        'enabled' => true,

        // Middleware to apply to routes
        'middleware' => ['auth:sanctum'],

        // Route prefix
        'prefix' => 'api/notification-preferences',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        // Channels enabled by default for new users
        // Use Laravel native channel names: 'mail', 'database', 'broadcast' or your custom ones
        'enabled_channels' => ['mail', 'database'],

        // Notification enabled by default for new users
        // When use for all enabled channels just like:
        //      'user.logged' => ['*']
        // When specify channels, use Laravel native names or your custom ones
        //      'user.logged' => ['mail']
        'enabled_notifications' => [
            'user.logged' => ['mail'],
        ],

        // Enable notification logging
        'log_notifications' => true,

        // Log table name (if you want to customize it)
        'log_table' => 'notification_logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Map notification types to their notification classes.
    | This allows you to use sendByType() method.
    |
    | All values must be valid notification class names.
    |
    */

    'notification_types' => [
        // System notifications
        'user.logged' => \Csouza\NotificationManagement\Notifications\UserLoggedNotification::class,

        // Your app notifications (examples):
        // 'order.created' => \App\Notifications\OrderCreated::class,
        // 'order.shipped' => \App\Notifications\OrderShipped::class,
        // 'order.delivered' => \App\Notifications\OrderDelivered::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event-to-Notification Mapping
    |--------------------------------------------------------------------------
    |
    | Automatically send notifications when Laravel events are fired.
    | This eliminates the need to create listener classes manually.
    |
    | Configuration options:
    | - notification_type: The notification type identifier
    | - notifiable: How to extract the user/entity to notify
    |   - String: Property name (e.g., 'user')
    |   - Dot notation: Nested property (e.g., 'order.user')
    |   - Closure: Custom extraction logic
    | - data: Additional data to pass to notification (optional)
    | - condition: Closure to check if notification should be sent (optional)
    | - enabled: Whether this event mapping is active (default: true)
    |
    */

    'event_notifications' => [
        // Example: Login event
        // \Illuminate\Auth\Events\Login::class => [
        //     'notification_type' => 'user.logged',
        //     'notifiable' => 'user', // $event->user
        //     'enabled' => true,
        // ],

        // Example: With nested relationship
        // \App\Events\OrderShipped::class => [
        //     'notification_type' => 'order.shipped',
        //     'notifiable' => 'order.user', // $event->order->user
        //     'data' => fn($event) => ['order_id' => $event->order->id],
        // ],

        // Example: With closure for complex logic
        // \App\Events\CommentPosted::class => [
        //     'notification_type' => 'comment.posted',
        //     'notifiable' => fn($event) => $event->comment->post->author,
        //     'data' => fn($event) => [
        //         'comment_id' => $event->comment->id,
        //         'post_id' => $event->comment->post->id,
        //     ],
        // ],

        // Example: Multiple notifiables (Collection)
        // \App\Events\PostPublished::class => [
        //     'notification_type' => 'post.published',
        //     'notifiable' => fn($event) => $event->post->subscribers,
        // ],

        // Example: Conditional notification
        // \App\Events\PaymentFailed::class => [
        //     'notification_type' => 'payment.failed',
        //     'notifiable' => 'user',
        //     'condition' => fn($event) => $event->attempts >= 3,
        // ],

        // Example: Disabled mapping
        // \App\Events\UserSaving::class => [
        //     'enabled' => false,
        // ],
    ],

];
