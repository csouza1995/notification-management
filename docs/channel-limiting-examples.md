# Real-World Use Cases for Channel Limiting

This document demonstrates practical scenarios where you'd want to limit notification channels.

## Use Case 1: Security Notifications (Force Channels)

**Scenario:** Security alerts must always be logged in the database for audit purposes, regardless of user preferences.

```php
<?php

namespace App\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class SuspiciousLoginDetected extends Notification
{
    use UsesNotificationPreferences;

    protected string $notificationType = 'security.suspicious.login';
    
    /**
     * Always force database logging for security audit trail
     * User preferences are ignored
     */
    protected array $forceChannels = ['database'];

    public function __construct(
        protected array $loginDetails
    ) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'security_alert',
            'ip' => $this->loginDetails['ip'],
            'location' => $this->loginDetails['location'],
            'timestamp' => $this->loginDetails['timestamp'],
            'severity' => 'high',
        ];
    }
}
```

**Why `forceChannels`?**
- Security events must be auditable
- Cannot rely on user preferences for compliance
- Database ensures permanent record

---

## Use Case 2: Critical System Alerts (Force Multiple Channels)

**Scenario:** Payment failures must notify user via email and SMS, no exceptions.

```php
<?php

namespace App\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification
{
    use UsesNotificationPreferences;

    protected string $notificationType = 'payment.failed';
    
    /**
     * Critical: always send via email AND SMS
     */
    protected array $forceChannels = ['mail', 'sms'];

    public function __construct(
        protected $invoice
    ) {}

    public function toMail(object $notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('Payment Failed - Action Required')
            ->line('We couldn\'t process your payment.')
            ->action('Update Payment Method', url('/billing'));
    }

    public function toSms(object $notifiable): string
    {
        return "Payment failed for invoice #{$this->invoice->id}. Update your payment method.";
    }
}
```

---

## Use Case 3: Marketing Emails (Allowed Channels)

**Scenario:** Marketing notifications should only be sent via email or in-app, never SMS.

```php
<?php

namespace App\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class WeeklyNewsletter extends Notification
{
    use UsesNotificationPreferences;

    protected string $notificationType = 'marketing.newsletter';
    
    /**
     * Limit to email and database, respect user preference within these
     * Even if user has SMS enabled, we won't use it
     */
    protected array $allowedChannels = ['mail', 'database'];

    public function toMail(object $notifiable)
    {
        return (new MailMessage)
            ->subject('Your Weekly Newsletter')
            ->markdown('emails.newsletter', [
                'user' => $notifiable,
                'articles' => $this->getTopArticles(),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'newsletter',
            'week' => now()->weekOfYear,
            'article_count' => count($this->getTopArticles()),
        ];
    }
}
```

**Why `allowedChannels`?**
- SMS too expensive for marketing
- Still respects user choice (email vs in-app)
- Filters out inappropriate channels

---

## Use Case 4: Time-Sensitive Notifications

**Scenario:** Flash sale alerts should use fast channels (push, SMS), not slow ones (email).

```php
<?php

namespace App\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class FlashSaleStarting extends Notification
{
    use UsesNotificationPreferences;

    protected string $notificationType = 'sale.flash';
    
    /**
     * Only use instant notification methods
     */
    protected array $allowedChannels = ['push', 'sms', 'broadcast'];

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '⚡ Flash Sale Started!',
            'body' => 'Limited time: 50% off everything!',
            'data' => ['url' => '/flash-sale'],
        ];
    }
}
```

---

## Use Case 5: Admin-Only Notifications

**Scenario:** System health alerts should only go to admin dashboard, never email/SMS.

```php
<?php

namespace App\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class ServerHighLoad extends Notification
{
    use UsesNotificationPreferences;

    protected string $notificationType = 'admin.server.load';
    
    /**
     * Dashboard only - don't spam admin emails
     */
    protected array $forceChannels = ['database'];

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'message' => 'Server load is high',
            'cpu_usage' => $this->cpuUsage,
            'memory_usage' => $this->memoryUsage,
            'severity' => 'warning',
        ];
    }
}
```

---

## Use Case 6: Compliance-Required Notifications

**Scenario:** Legal notifications must be sent via email for proof of delivery.

```php
<?php

namespace App\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Notifications\Notification;

class PrivacyPolicyUpdate extends Notification
{
    use UsesNotificationPreferences;

    protected string $notificationType = 'legal.privacy.update';
    
    /**
     * Legal requirement: must send via email
     * Also log for audit trail
     */
    protected array $forceChannels = ['mail', 'database'];

    public function toMail(object $notifiable)
    {
        return (new MailMessage)
            ->subject('Important: Privacy Policy Update')
            ->line('We\'ve updated our Privacy Policy.')
            ->line('Please review the changes.')
            ->action('View Policy', url('/privacy-policy'))
            ->line('This notification is required by law.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'legal_notice',
            'document' => 'privacy_policy',
            'version' => '2.0',
            'sent_at' => now(),
        ];
    }
}
```

---

## Decision Matrix

Use this table to decide which feature to use:

| Scenario | Use | Example |
|----------|-----|---------|
| Must use specific channels for all users | `forceChannels` | Security logs, legal notices |
| Want to limit available channels but respect user choice | `allowedChannels` | Marketing (no SMS), time-sensitive (no email) |
| Fully respect user preferences | Neither (default) | Regular notifications |
| Critical alerts that override preferences | `forceChannels` | Payment failures, security alerts |
| Cost-sensitive notifications | `allowedChannels` | Exclude expensive channels (SMS) |

---

## Combining with Configuration

You can also set defaults in config and override per notification:

```php
// config/notification-management.php
'defaults' => [
    'enabled_notifications' => [
        'marketing.*' => ['mail'], // Marketing only via email by default
        'security.*' => ['*'],     // Security via all channels
    ],
],
```

Then override in specific notifications:

```php
class SpecialPromotion extends Notification
{
    use UsesNotificationPreferences;
    
    protected string $notificationType = 'marketing.special';
    
    // This special promo can use SMS too
    protected array $allowedChannels = ['mail', 'sms'];
}
```

---

## Testing Channel Limits

```php
// Test that forceChannels works
test('security notification always uses database', function () {
    $user = User::factory()->create();
    
    // User disables all notifications
    $user->disableNotificationChannel('security.alert', 'database');
    
    $notification = new SecurityAlert();
    
    // Should still use database due to forceChannels
    expect($notification->via($user))->toBe(['database']);
});

// Test that allowedChannels filters correctly
test('marketing notification respects allowed channels', function () {
    $user = User::factory()->create();
    
    // User enables mail and SMS
    $user->enableNotificationChannel('marketing.promo', 'mail');
    $user->enableNotificationChannel('marketing.promo', 'sms');
    
    $notification = new Newsletter();
    
    // Should only use mail (allowedChannels = ['mail'])
    expect($notification->via($user))->toBe(['mail']);
});
```

---

## Best Practices

1. **Use `forceChannels` sparingly** - Only for critical/legal requirements
2. **Prefer `allowedChannels`** - Still respects user choice within limits
3. **Document your decision** - Add comments explaining why you're forcing channels
4. **Test thoroughly** - Ensure overrides work as expected
5. **Consider costs** - Use `allowedChannels` to exclude expensive channels (SMS)
6. **Audit trail** - Always include `database` in `forceChannels` for security/legal notifications

## See Also

- [Main Documentation](../README.md)
- [Using the Trait](./using-trait.md)
