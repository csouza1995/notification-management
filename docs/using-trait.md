# Using the UsesNotificationPreferences Trait

## Why Use This Trait?

Without this trait, you'd need to manually implement the `via()` method in every notification class to check user preferences:

```php
// ❌ WITHOUT the trait - repetitive code in every notification
class OrderShipped extends Notification
{
    public function via(object $notifiable): array
    {
        // This gets repeated in EVERY notification!
        return $notifiable->getActiveChannelsFor('order.shipped');
    }
    
    // ... rest of the notification
}

class OrderDelivered extends Notification
{
    public function via(object $notifiable): array
    {
        // Same code again!
        return $notifiable->getActiveChannelsFor('order.delivered');
    }
    
    // ... rest of the notification
}
```

With the trait, you just declare the notification type once:

```php
// ✅ WITH the trait - clean and DRY
class OrderShipped extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'order.shipped';
    
    // That's it! The via() method is handled automatically
}
```

## Basic Usage

### 1. Add the Trait to Your Notification

```php
use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
{
    use UsesNotificationPreferences;
    
    /**
     * Define the notification type identifier
     * This should match the key in config/notification-management.php
     */
    protected string $notificationType = 'order.shipped';
    
    // Your notification implementation...
}
```

### 2. Configure Notification Types (Optional)

Add your notification types to `config/notification-management.php`:

```php
'notification_types' => [
    'order.shipped' => \App\Notifications\OrderShipped::class,
    'order.delivered' => \App\Notifications\OrderDelivered::class,
    'user.mentioned' => \App\Notifications\UserMentioned::class,
],
```

### 3. Set Default Preferences (Optional)

Configure which channels are enabled by default for new users:

```php
'defaults' => [
    'enabled_channels' => ['mail', 'database'],
    
    'enabled_notifications' => [
        'order.shipped' => ['mail', 'database'],
        'order.delivered' => ['*'], // All available channels
        'marketing.promo' => ['mail'], // Email only
    ],
],
```

## Complete Example

```php
<?php

namespace App\Notifications;

use App\Models\Order;
use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationPreferences;

    /**
     * Notification type identifier
     */
    protected string $notificationType = 'order.shipped';

    public function __construct(
        protected Order $order
    ) {}

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Order Has Been Shipped!')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Great news! Your order #'.$this->order->id.' has been shipped.')
            ->line('**Order Details:**')
            ->line('Tracking Code: '.$this->order->tracking_code)
            ->line('Estimated Delivery: '.$this->order->estimated_delivery)
            ->action('Track Your Order', url('/orders/'.$this->order->id.'/track'))
            ->line('Thank you for your purchase!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order.shipped',
            'title' => 'Order Shipped',
            'message' => 'Your order #'.$this->order->id.' has been shipped',
            'order_id' => $this->order->id,
            'tracking_code' => $this->order->tracking_code,
            'estimated_delivery' => $this->order->estimated_delivery,
            'action_url' => url('/orders/'.$this->order->id.'/track'),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'Your order has been shipped!',
            'tracking_code' => $this->order->tracking_code,
        ];
    }
}
```

## Advanced Usage

### Limiting to Specific Channels

Sometimes you need to force a notification to use only specific channels, regardless of user preferences:

#### Force Channels (Ignores User Preferences)

```php
class SecurityAlert extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'security.alert';
    
    /**
     * Always send via database, ignore user preferences
     */
    protected array $forceChannels = ['database'];
}
```

#### Allowed Channels (Intersects with User Preferences)

```php
class MarketingEmail extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'marketing.promo';
    
    /**
     * Only allow mail and database, even if user has SMS enabled
     */
    protected array $allowedChannels = ['mail', 'database'];
}
```

**Differences:**

| Property | Behavior | Use Case |
|----------|----------|----------|
| `$forceChannels` | **Ignores** user preferences completely | Critical notifications that must use specific channels |
| `$allowedChannels` | **Intersects** with user preferences | Limit available channels while still respecting user choice |

**Example:**

```php
// User has enabled: ['mail', 'database', 'sms']

// With forceChannels
protected array $forceChannels = ['database'];
// Result: ['database'] - user preferences ignored

// With allowedChannels
protected array $allowedChannels = ['mail', 'database'];
// Result: ['mail', 'database'] - SMS filtered out

// With allowedChannels (no intersection)
protected array $allowedChannels = ['telegram', 'slack'];
// Result: [] - empty array, notification won't be sent
```

### Custom Type Detection

If you don't want to set `$notificationType`, the trait will automatically guess it from the class name:

```php
// OrderShipped → order.shipped
// UserMentioned → user.mentioned
// PaymentReceived → payment.received

class OrderShipped extends Notification
{
    use UsesNotificationPreferences;
    
    // No need to set $notificationType
    // It will automatically be 'order.shipped'
}
```

### Overriding the via() Method

If you need custom logic, you can still override the `via()` method:

```php
class UrgentNotification extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'urgent.alert';
    
    /**
     * Custom via() logic
     */
    public function via(object $notifiable): array
    {
        $channels = parent::via($notifiable); // Get user preferences
        
        // Force SMS for urgent notifications
        if (!in_array('sms', $channels)) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }
}
```

### Error Handling

The trait validates that the notifiable has the required trait:

```php
// ✅ Correct - User has HasNotificationPreferences trait
$user->notify(new OrderShipped($order));

// ❌ Error - Notifiable doesn't have the trait
$admin->notify(new OrderShipped($order));
// RuntimeException: Notifiable entity must use HasNotificationPreferences trait
```

## Migrating Existing Notifications

### Before (Manual Implementation)

```php
class OrderShipped extends Notification
{
    public function via(object $notifiable): array
    {
        if (method_exists($notifiable, 'getActiveChannelsFor')) {
            return $notifiable->getActiveChannelsFor('order.shipped');
        }
        
        return ['mail', 'database']; // Fallback
    }
    
    // ... rest of notification
}
```

### After (Using Trait)

```php
class OrderShipped extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'order.shipped';
    
    // via() method is now automatic!
    // ... rest of notification stays the same
}
```

## Benefits

✅ **DRY Code**: No repetition of the `via()` method  
✅ **Automatic**: Respects user preferences without manual checks  
✅ **Flexible**: Can still override when needed  
✅ **Type-Safe**: Validates notifiable has required trait  
✅ **Convention**: Auto-detects notification type from class name  
✅ **Testable**: Easy to test notification behavior

## See Also

- [User Login Notification Example](./user-logged-notification.md)
- [Main Documentation](../README.md)
