# Automatic Event-to-Notification Mapping

One of the most powerful features of this package is the ability to automatically send notifications when Laravel events are fired, without needing to create listener classes manually.

## Overview

Instead of creating a listener class for each event:

```php
// ❌ Traditional way - repetitive
class SendOrderShippedNotification implements ShouldQueue
{
    public function handle(OrderShipped $event)
    {
        NotificationManager::sendByType(
            $event->order->user,
            'order.shipped',
            ['order_id' => $event->order->id]
        );
    }
}

// Then register in EventServiceProvider
protected $listen = [
    OrderShipped::class => [SendOrderShippedNotification::class],
];
```

You can simply configure it:

```php
// ✅ New way - just configuration
'event_notifications' => [
    \App\Events\OrderShipped::class => [
        'notification_type' => 'order.shipped',
        'notifiable' => 'order.user',
        'data' => fn($event) => ['order_id' => $event->order->id],
    ],
],
```

## Basic Usage

### 1. Configure Event Mapping

Edit `config/notification-management.php`:

```php
'event_notifications' => [
    // Simple property extraction
    \Illuminate\Auth\Events\Login::class => [
        'notification_type' => 'user.logged',
        'notifiable' => 'user', // Accesses $event->user
    ],
],
```

### 2. That's it!

No need to:
- ❌ Create listener class
- ❌ Register in EventServiceProvider
- ❌ Write boilerplate code

The package automatically registers the listener and sends the notification when the event fires.

## Configuration Options

### `notification_type` (required)

The notification type identifier that maps to a notification class in `notification_types` config.

```php
'notification_type' => 'order.shipped',
```

### `notifiable` (required)

How to extract the user/entity to notify from the event. Supports:

#### String: Simple Property

```php
'notifiable' => 'user', // Accesses $event->user
```

#### Dot Notation: Nested Properties

```php
'notifiable' => 'order.user', // Accesses $event->order->user
'notifiable' => 'comment.post.author', // Accesses $event->comment->post->author
```

#### Closure: Custom Logic

```php
'notifiable' => fn($event) => $event->comment->post->author,
```

#### Collection: Multiple Notifiables

```php
'notifiable' => fn($event) => $event->post->subscribers, // Notifies all subscribers
```

### `data` (optional)

Additional data to pass to the notification.

#### Array: Static Data

```php
'data' => ['source' => 'event_listener'],
```

#### Closure: Dynamic Data

```php
'data' => fn($event) => [
    'order_id' => $event->order->id,
    'tracking_code' => $event->order->tracking_code,
    'estimated_delivery' => $event->order->estimated_delivery,
],
```

### `condition` (optional)

Only send the notification if the condition returns `true`.

```php
'condition' => fn($event) => $event->attempts >= 3,
```

```php
'condition' => fn($event) => $event->order->total > 1000, // Only for orders over $1000
```

### `enabled` (optional, default: `true`)

Disable an event mapping without removing it from config.

```php
'enabled' => false, // Temporarily disable this mapping
```

## Complete Examples

### Example 1: Login Notification

```php
\Illuminate\Auth\Events\Login::class => [
    'notification_type' => 'user.logged',
    'notifiable' => 'user',
    'data' => fn($event) => [
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'logged_at' => now()->toDateTimeString(),
    ],
],
```

### Example 2: Order Shipped

```php
\App\Events\OrderShipped::class => [
    'notification_type' => 'order.shipped',
    'notifiable' => 'order.user',
    'data' => fn($event) => [
        'order_id' => $event->order->id,
        'tracking_code' => $event->order->tracking_code,
        'carrier' => $event->order->carrier,
    ],
],
```

### Example 3: Comment Posted (Nested Relationship)

```php
\App\Events\CommentPosted::class => [
    'notification_type' => 'comment.posted',
    'notifiable' => fn($event) => $event->comment->post->author,
    'data' => fn($event) => [
        'comment_id' => $event->comment->id,
        'commenter_name' => $event->comment->user->name,
        'post_id' => $event->comment->post->id,
        'post_title' => $event->comment->post->title,
    ],
],
```

### Example 4: Payment Failed (with Condition)

```php
\App\Events\PaymentFailed::class => [
    'notification_type' => 'payment.failed',
    'notifiable' => 'user',
    'condition' => fn($event) => $event->attempts >= 3, // Only after 3 failed attempts
    'data' => fn($event) => [
        'invoice_id' => $event->invoice->id,
        'amount' => $event->invoice->amount,
        'attempts' => $event->attempts,
    ],
],
```

### Example 5: Post Published (Multiple Notifiables)

```php
\App\Events\PostPublished::class => [
    'notification_type' => 'post.published',
    'notifiable' => fn($event) => $event->post->subscribers, // Collection of users
    'data' => fn($event) => [
        'post_id' => $event->post->id,
        'post_title' => $event->post->title,
        'author_name' => $event->post->author->name,
    ],
],
```

### Example 6: Disabled Mapping

```php
\App\Events\UserSaving::class => [
    'enabled' => false, // Temporarily disabled, maybe it was firing too often
    'notification_type' => 'user.updated',
    'notifiable' => fn($event) => $event->user,
],
```

## Advanced Patterns

### Conditional Notification Based on User Role

```php
\App\Events\AdminActionPerformed::class => [
    'notification_type' => 'admin.action',
    'notifiable' => fn($event) => User::whereHas('roles', fn($q) => $q->where('name', 'super-admin'))->get(),
    'condition' => fn($event) => $event->action->severity === 'critical',
],
```

### Notify Different Users Based on Event Data

```php
\App\Events\TicketAssigned::class => [
    'notification_type' => 'ticket.assigned',
    'notifiable' => fn($event) => [$event->ticket->assignee, $event->ticket->creator],
    'data' => fn($event) => [
        'ticket_id' => $event->ticket->id,
        'title' => $event->ticket->title,
    ],
],
```

### Using Event Method Instead of Property

```php
\App\Events\OrderStatusChanged::class => [
    'notification_type' => 'order.status.changed',
    'notifiable' => fn($event) => $event->getNotifiableUsers(), // Method on event class
    'data' => fn($event) => $event->toNotificationData(),
],
```

## Error Handling

The package automatically catches exceptions to prevent event flow from breaking:

```php
// If extraction or notification fails, it's logged but doesn't break the event
try {
    $mapper->handle($event, $config);
} catch (\Exception $e) {
    report($e); // Logs error but continues
}
```

## Testing

### Testing Event Notifications

```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('order shipped event sends notification', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    
    Notification::fake();
    
    event(new OrderShipped($order));
    
    Notification::assertSentTo(
        $user,
        OrderShippedNotification::class
    );
});
```

### Disabling Event Notifications in Tests

```php
// In specific test
config(['notification-management.event_notifications' => []]);

// Or disable specific event
config([
    'notification-management.event_notifications.App\Events\OrderShipped' => [
        'enabled' => false,
    ],
]);
```

## Performance Considerations

### When to Use

✅ **Good for:**
- User-facing events (login, order updates, comments)
- Infrequent events
- Events that naturally trigger notifications

❌ **Avoid for:**
- High-frequency events (model saving, every request)
- Events that fire hundreds of times per second
- Background jobs that run continuously

### Optimization Tips

1. **Use Conditions**: Filter unnecessary notifications
   ```php
   'condition' => fn($event) => $event->shouldNotify(),
   ```

2. **Queue Notifications**: Ensure notification class implements `ShouldQueue`
   ```php
   class OrderShipped extends Notification implements ShouldQueue
   ```

3. **Disable in Environments**:
   ```php
   // In config/notification-management.php
   'event_notifications' => app()->environment('testing') ? [] : [
       // Your mappings
   ],
   ```

## Debugging

### Check Registered Listeners

```php
// In tinker or controller
Event::getRawListeners(\App\Events\OrderShipped::class);
```

### Log Event Notifications

Add to your event mapping:

```php
'data' => fn($event) => tap(
    ['order_id' => $event->order->id],
    fn($data) => \Log::info('Sending order shipped notification', $data)
),
```

## Comparison with Manual Listeners

| Feature | Event Mapping | Manual Listener |
|---------|---------------|-----------------|
| Setup | Config only | Class + registration |
| Type safety | Runtime only | IDE autocomplete |
| Flexibility | High (closures) | Very high (full class) |
| Debugging | Harder | Easier |
| Code organization | Centralized config | Spread across files |
| Best for | Simple mappings | Complex logic |

## Recommendation

Use **event notifications** for:
- ✅ Simple event → notification mappings
- ✅ Rapid development
- ✅ Consistent patterns

Use **manual listeners** for:
- ✅ Complex business logic
- ✅ Multiple operations per event
- ✅ Heavy computation or external API calls

Or use **both together** - automatic for simple cases, manual for complex ones!

## See Also

- [Main Documentation](../README.md)
- [Using Notification Trait](./using-trait.md)
- [Channel Limiting Examples](./channel-limiting-examples.md)
