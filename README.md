# Laravel Notification Management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/csouza/notification-management.svg?style=flat-square)](https://packagist.org/packages/csouza/notification-management)
[![Total Downloads](https://img.shields.io/packagist/dt/csouza/notification-management.svg?style=flat-square)](https://packagist.org/packages/csouza/notification-management)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/csouza1995/notification-management/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/csouza1995/notification-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/csouza1995/notification-management/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/csouza1995/notification-management/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

A powerful Laravel package that allows users to manage their notification preferences across multiple channels. Let your users decide how they want to be notified!

## Features

✨ **User Preferences**: Let users choose which channels they want to receive notifications through
📱 **Multiple Channels**: Support for Laravel native channels (mail, database, broadcast) and custom channels
🔧 **Easy Integration**: Simple trait to add to your User model
📊 **Notification Logging**: Track all sent notifications
🎯 **Type-based Control**: Different preferences per notification type
🚀 **API Ready**: Built-in REST API for managing preferences
🔐 **Built-in Notifications**: Includes `user.logged` notification out of the box

## Installation

Install the package via composer:

```bash
composer require csouza/notification-management
```

Publish the config and migrations:

```bash
php artisan vendor:publish --tag=notification-management-config
php artisan vendor:publish --tag=notification-management-migrations
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Add the trait to your User model:

```php
use Csouza\NotificationManagement\Traits\HasNotificationPreferences;

class User extends Authenticatable
{
    use HasNotificationPreferences;
}
```

## Usage

### Managing User Preferences

```php
// Enable a channel for a notification type
$user->enableNotificationChannel('order.shipped', 'mail');
$user->enableNotificationChannel('order.shipped', 'database');

// Disable a channel
$user->disableNotificationChannel('order.shipped', 'sms');

// Check if user wants to receive via a channel
if ($user->wantsNotificationVia('order.shipped', 'mail')) {
    // User wants email notifications for shipped orders
}

// Get all active channels for a notification type
$channels = $user->getActiveChannelsFor('order.shipped');
// Returns: ['mail', 'database']

// Set bulk preferences
$user->setNotificationPreferences([
    'order.shipped' => ['mail' => true, 'sms' => false],
    'order.delivered' => ['mail' => true, 'database' => true],
]);
```

### Creating Notifications

Use the `UsesNotificationPreferences` trait in your notification classes to automatically respect user preferences:

```php
use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderShipped extends Notification
{
    use UsesNotificationPreferences;

    /**
     * Define the notification type identifier
     */
    protected string $notificationType = 'order.shipped';

    public function __construct(
        protected Order $order
    ) {}

    /**
     * No need to define via() - the trait handles it automatically
     * based on user preferences!
     */

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your order has been shipped!')
            ->line('Your order #'.$this->order->id.' is on its way!')
            ->action('Track Order', url('/orders/'.$this->order->id));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'Your order has been shipped',
        ];
    }
}
```

The trait automatically:
- ✅ Checks user preferences for the notification type
- ✅ Returns only enabled channels
- ✅ Respects default configurations
- ✅ No need to manually implement the `via()` method!

**Advanced: Limiting Channels**

```php
// Force specific channels (ignores user preferences)
class SecurityAlert extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'security.alert';
    protected array $forceChannels = ['database']; // Always use database
}

// Limit allowed channels (intersects with user preferences)
class MarketingEmail extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'marketing.promo';
    protected array $allowedChannels = ['mail']; // Only allow email, filter out others
}
```

### Sending Notifications

The package integrates seamlessly with Laravel's notification system:

```php
use App\Notifications\OrderShipped;
use Csouza\NotificationManagement\Facades\NotificationManager;

// Send notification respecting user preferences
NotificationManager::send($user, 'order.shipped', OrderShipped::class, [
    'order_id' => $order->id,
    'tracking_code' => $order->tracking_code,
]);

// Or using the notification instance
NotificationManager::send($user, 'order.shipped', new OrderShipped($order));

// Send by type (requires notification_types config)
NotificationManager::sendByType($user, 'order.shipped', [
    'order_id' => $order->id,
]);
```

### Automatic Event-to-Notification Mapping 🚀

**New!** Automatically send notifications when Laravel events are fired, without creating listener classes:

```php
// config/notification-management.php
'event_notifications' => [
    // Simple: property extraction
    \Illuminate\Auth\Events\Login::class => [
        'notification_type' => 'user.logged',
        'notifiable' => 'user', // $event->user
    ],
    
    // Nested: dot notation
    \App\Events\OrderShipped::class => [
        'notification_type' => 'order.shipped',
        'notifiable' => 'order.user', // $event->order->user
        'data' => fn($event) => ['order_id' => $event->order->id],
    ],
    
    // Advanced: closure with condition
    \App\Events\PaymentFailed::class => [
        'notification_type' => 'payment.failed',
        'notifiable' => 'user',
        'condition' => fn($event) => $event->attempts >= 3,
    ],
    
    // Multiple: notify collection of users
    \App\Events\PostPublished::class => [
        'notification_type' => 'post.published',
        'notifiable' => fn($event) => $event->post->subscribers,
    ],
],
```

**Benefits:**
- ✅ No listener classes needed
- ✅ Configuration-driven
- ✅ Supports closures for complex logic
- ✅ Conditional notifications
- ✅ Multiple notifiables (collections)

See [Event Notifications Documentation](docs/event-notifications.md) for complete guide.

### Built-in User Login Notification

The package includes a ready-to-use notification for user logins. Simply register the listener:

```php
// app/Providers/EventServiceProvider.php
use Illuminate\Auth\Events\Login;
use Csouza\NotificationManagement\Listeners\SendUserLoggedNotification;

protected $listen = [
    Login::class => [
        SendUserLoggedNotification::class,
    ],
];
```

Now users will automatically receive notifications when they log in, including:
- IP Address
- Browser/User Agent
- Location (if configured)
- Login timestamp

Users can control how they receive these notifications:

```php
// Enable email notifications for logins
$user->enableNotificationChannel('user.logged', 'mail');

// Disable SMS notifications for logins
$user->disableNotificationChannel('user.logged', 'sms');
```

See [docs/user-logged-notification.md](docs/user-logged-notification.md) for full documentation.

### Custom Channels

Register custom channels in your `AppServiceProvider`:

```php
use Csouza\NotificationManagement\Managers\ChannelRegistry;

public function boot()
{
    $registry = app(ChannelRegistry::class);
    
    $registry->register('sms', \App\Channels\SmsChannel::class);
    $registry->register('telegram', \App\Channels\TelegramChannel::class);
    $registry->register('slack', \App\Channels\SlackChannel::class);
}
```

Or add them to `config/notification-management.php`:

```php
'channels' => [
    'sms' => [
        'driver' => \App\Channels\SmsChannel::class,
        'enabled' => true,
        'description' => 'SMS notifications',
    ],
    'telegram' => [
        'driver' => \App\Channels\TelegramChannel::class,
        'enabled' => true,
        'description' => 'Telegram notifications',
    ],
],
```

### API Endpoints

The package provides REST API endpoints for managing preferences:

```
GET    /api/notification-preferences          # Get all preferences
PUT    /api/notification-preferences          # Update preferences
POST   /api/notification-preferences/enable   # Enable a channel
POST   /api/notification-preferences/disable  # Disable a channel
GET    /api/notification-preferences/channels # Get available channels
GET    /api/notification-preferences/types    # Get notification types
GET    /api/notification-preferences/history  # Get notification history
```

**Example requests:**

```javascript
// Enable a channel
POST /api/notification-preferences/enable
{
    "notification_type": "order.shipped",
    "channel": "mail"
}

// Bulk update preferences
PUT /api/notification-preferences
{
    "preferences": {
        "order.shipped": {
            "mail": true,
            "sms": false,
            "database": true
        },
        "order.delivered": {
            "mail": true,
            "database": true
        }
    }
}
```

### Notification History

Track notification history for users:

```php
// Get all notification history
$history = $user->getNotificationHistory();

// Get history for specific notification type
$history = $user->getNotificationHistory('order.shipped', 50);

// Via the manager
$history = NotificationManager::getNotificationHistory($user, 'order.shipped', 100);
```

## Configuration Options

Edit `config/notification-management.php`:

```php
return [
    // Register custom channels
    'channels' => [
        // Your custom channels here
    ],

    // Routes configuration
    'routes' => [
        'enabled' => true,
        'middleware' => ['auth:sanctum'],
        'prefix' => 'api/notification-preferences',
    ],

    // Default settings
    'defaults' => [
        'enabled_channels' => ['mail', 'database'], // Default channels for new users
        'log_notifications' => true,                 // Enable logging
        'log_table' => 'notification_logs',
    ],

    // Define your notification types
    'notification_types' => [
        'order.created' => 'Order created',
        'order.shipped' => 'Order shipped',
        'order.delivered' => 'Order delivered',
        'user.mentioned' => 'You were mentioned',
    ],
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email carlossouza.work@gmail.com instead of using the issue tracker.

## Credits

- [Carlos Souza](https://github.com/csouza1995)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
