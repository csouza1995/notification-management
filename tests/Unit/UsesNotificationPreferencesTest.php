<?php

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set config for testing
    config([
        'notification-management.defaults.enabled_channels' => ['mail', 'database'],
        'notification-management.defaults.enabled_notifications' => [
            'custom.type' => ['mail'],
        ],
    ]);

    // Create a simple user model for testing
    $this->userClass = new class extends Model
    {
        use \Csouza\NotificationManagement\Traits\HasNotificationPreferences;

        protected $table = 'users';

        protected $fillable = ['name', 'email'];

        public $timestamps = false;
    };

    // Create users table
    \Illuminate\Support\Facades\Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
    });

    // Create user instance
    $this->user = $this->userClass::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});

it('uses notification type property when available', function () {
    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'custom.type';
    };

    $channels = $notification->via($this->user);

    expect($channels)->toBeArray();
});

it('throws exception when notifiable does not have HasNotificationPreferences trait', function () {
    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'test.type';
    };

    $notifiable = new class {};

    $notification->via($notifiable);
})->throws(\RuntimeException::class, 'must use HasNotificationPreferences trait');

it('respects user preferences when getting channels', function () {
    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'custom.type';
    };

    $channels = $notification->via($this->user);

    expect($channels)->toBeArray()
        ->and($channels)->toContain('mail');
});

it('forces specific channels when forceChannels is set', function () {
    // User has mail enabled
    config(['notification-management.defaults.enabled_notifications.custom.type' => ['mail', 'database']]);

    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'custom.type';

        protected array $forceChannels = ['database']; // Force only database
    };

    $channels = $notification->via($this->user);

    expect($channels)->toBe(['database'])
        ->and($channels)->not->toContain('mail'); // User preference ignored
});

it('limits to allowed channels when allowedChannels is set', function () {
    // Configure before creating user
    config([
        'notification-management.defaults.enabled_notifications' => [
            'test.limit' => ['mail', 'database', 'sms'],
        ],
    ]);

    // Recreate user to get new preferences
    $user = $this->userClass::create([
        'name' => 'Test User 2',
        'email' => 'test2@example.com',
    ]);

    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'test.limit';

        protected array $allowedChannels = ['mail', 'sms']; // Only allow mail and sms
    };

    $channels = $notification->via($user);

    // Should intersect user channels (mail, database, sms) with allowed (mail, sms)
    expect($channels)->toContain('mail')
        ->and($channels)->toContain('sms')
        ->and($channels)->not->toContain('database'); // Filtered out
});

it('returns empty array when allowedChannels does not intersect with user preferences', function () {
    // Configure before creating user
    config([
        'notification-management.defaults.enabled_notifications' => [
            'test.empty' => ['mail'],
        ],
    ]);

    // Recreate user
    $user = $this->userClass::create([
        'name' => 'Test User 3',
        'email' => 'test3@example.com',
    ]);

    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'test.empty';

        protected array $allowedChannels = ['sms', 'telegram']; // User has none of these
    };

    $channels = $notification->via($user);

    expect($channels)->toBeEmpty();
});

it('forceChannels takes precedence over allowedChannels', function () {
    $notification = new class extends Notification
    {
        use UsesNotificationPreferences;

        protected string $notificationType = 'custom.type';

        protected array $forceChannels = ['database'];

        protected array $allowedChannels = ['mail', 'sms'];
    };

    $channels = $notification->via($this->user);

    // forceChannels wins
    expect($channels)->toBe(['database']);
});
