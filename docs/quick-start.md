# Quick Start Guide

This guide will help you get started with Laravel Notification Management in 5 minutes.

## Installation

```bash
composer require csouza/notification-management
```

## Setup

### 1. Publish Config and Migrations

```bash
php artisan vendor:publish --tag=notification-management-config
php artisan vendor:publish --tag=notification-management-migrations
php artisan migrate
```

### 2. Add Trait to User Model

```php
use Csouza\NotificationManagement\Traits\HasNotificationPreferences;

class User extends Authenticatable
{
    use HasNotificationPreferences;
}
```

### 3. Configure Defaults (Optional)

Edit `config/notification-management.php`:

```php
'defaults' => [
    'enabled_channels' => ['mail', 'database'],
    'enabled_notifications' => [
        'order.shipped' => ['mail', 'database'],
    ],
],
```

## Usage

### Create a Notification

```php
use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;

class OrderShipped extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'order.shipped';
    
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your order has been shipped!')
            ->line('Your order is on its way!');
    }
}
```

### Register Notification Type

In `config/notification-management.php`:

```php
'notification_types' => [
    'order.shipped' => \App\Notifications\OrderShipped::class,
],
```

### Send Notification

```php
use Csouza\NotificationManagement\Facades\NotificationManager;

NotificationManager::sendByType($user, 'order.shipped', [
    'order_id' => 123,
]);
```

### Or Map to Event (Automatic)

```php
'event_notifications' => [
    \App\Events\OrderShipped::class => [
        'notification_type' => 'order.shipped',
        'notifiable' => 'order.user',
    ],
],
```

## User Preferences

Users can manage their preferences via API or programmatically:

```php
// Enable channel for notification type
$user->enableNotificationChannel('order.shipped', 'mail');

// Disable channel
$user->disableNotificationChannel('order.shipped', 'sms');

// Check preferences
$user->wantsNotificationVia('order.shipped', 'mail'); // true/false
```

## API Endpoints

The package provides REST API endpoints:

```
GET    /api/notification-preferences          # Get all preferences
PUT    /api/notification-preferences          # Update preferences
POST   /api/notification-preferences/enable   # Enable a channel
POST   /api/notification-preferences/disable  # Disable a channel
GET    /api/notification-preferences/channels # Get available channels
GET    /api/notification-preferences/types    # Get notification types
GET    /api/notification-preferences/history  # Get notification history
```

## Next Steps

- Read the [full documentation](../README.md)
- Learn about [event-to-notification mapping](./event-notifications.md)
- Explore [channel limiting](./channel-limiting-examples.md)
- Check out [notification trait usage](./using-trait.md)

## Support

- Issues: https://github.com/csouza1995/notification-management/issues
- Email: carlossouza.work@gmail.com
