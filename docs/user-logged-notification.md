# User Logged Notification

This package includes a built-in notification for user logins.

## Setup

### 1. Register the Event Listener

Add to your `EventServiceProvider`:

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

Or register in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php

use Illuminate\Auth\Events\Login;
use Csouza\NotificationManagement\Listeners\SendUserLoggedNotification;
use Illuminate\Support\Facades\Event;

public function boot()
{
    Event::listen(
        Login::class,
        SendUserLoggedNotification::class,
    );
}
```

### 2. Configure User Preferences

Users can control how they receive login notifications:

```php
// Enable email notifications for logins
$user->enableNotificationChannel('user.logged', 'mail');

// Enable in-app notifications
$user->enableNotificationChannel('user.logged', 'database');

// Disable SMS notifications
$user->disableNotificationChannel('user.logged', 'sms');
```

### 3. Default Behavior

By default, users will receive login notifications via channels defined in `config/notification-management.php`:

```php
'defaults' => [
    'enabled_channels' => ['mail', 'database'],
],
```

## Customization

### Customize the Notification

Extend or replace the notification class:

```php
namespace App\Notifications;

use Csouza\NotificationManagement\Notifications\UserLoggedNotification as BaseNotification;

class CustomUserLoggedNotification extends BaseNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Custom: New Login')
            ->line('Your custom message here...');
    }
}
```

Then use your custom notification:

```php
NotificationManager::send(
    $user,
    'user.logged',
    new CustomUserLoggedNotification($loginDetails)
);
```

### Add Location Tracking

Integrate with a geolocation service:

```php
// Install package
composer require torann/geoip

// In SendUserLoggedNotification listener
protected function getLocationFromIp(string $ip): ?string
{
    $location = geoip($ip);
    return $location->city . ', ' . $location->country;
}
```

### Security Features

You can extend this to add security features:

```php
// Detect suspicious logins
if ($this->isSuspiciousLogin($user, $loginDetails)) {
    // Send different notification
    NotificationManager::send(
        $user,
        'user.suspicious_login',
        new SuspiciousLoginNotification($loginDetails)
    );
    
    // Lock account
    $user->lockAccount();
}
```

## API Usage

Users can manage their preferences via API:

```javascript
// Disable login notifications
POST /api/notification-preferences/disable
{
    "notification_type": "user.logged",
    "channel": "mail"
}

// Check current preferences
GET /api/notification-preferences
```

## Example Email

When a user logs in, they receive an email like:

```
Subject: New Login Detected

Hello John Doe!

We detected a new login to your account.

Login Details:
IP Address: 192.168.1.1
Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64)
Location: São Paulo, Brazil
Time: 2025-11-21 10:30:00

If this wasn't you, please secure your account immediately.

[View Account Activity]
```

## Queue Configuration

The notification implements `ShouldQueue`, so make sure your queue is running:

```bash
php artisan queue:work
```

## Testing

```php
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Notification;

it('sends notification when user logs in', function () {
    Notification::fake();
    
    $user = User::factory()->create();
    
    event(new Login('web', $user, false));
    
    Notification::assertSentTo($user, UserLoggedNotification::class);
});
```
