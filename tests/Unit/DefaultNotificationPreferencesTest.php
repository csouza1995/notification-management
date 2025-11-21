<?php

use Csouza\NotificationManagement\Models\UserNotificationPreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set config for testing
    config([
        'notification-management.defaults.enabled_notifications' => [
            'user.logged' => ['mail'],
            'order.shipped' => ['mail', 'database'],
            'marketing.promo' => ['*'],
        ],
        'notification-management.defaults.enabled_channels' => ['mail', 'database', 'sms'],
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
});

it('initializes default preferences when user is created', function () {
    $user = $this->userClass::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Should have preferences for user.logged (mail only)
    $userLoggedPrefs = UserNotificationPreference::where('user_id', $user->id)
        ->where('notification_type', 'user.logged')
        ->get();

    expect($userLoggedPrefs)->toHaveCount(1)
        ->and($userLoggedPrefs->first()->channel_name)->toBe('mail')
        ->and($userLoggedPrefs->first()->is_enabled)->toBeTrue();

    // Should have preferences for order.shipped (mail and database)
    $orderShippedPrefs = UserNotificationPreference::where('user_id', $user->id)
        ->where('notification_type', 'order.shipped')
        ->pluck('channel_name')
        ->toArray();

    expect($orderShippedPrefs)->toContain('mail')
        ->and($orderShippedPrefs)->toContain('database')
        ->and($orderShippedPrefs)->toHaveCount(2);
});

it('uses wildcard to enable all default channels', function () {
    $user = $this->userClass::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    // marketing.promo has '*' so should use all enabled_channels
    $marketingPrefs = UserNotificationPreference::where('user_id', $user->id)
        ->where('notification_type', 'marketing.promo')
        ->pluck('channel_name')
        ->toArray();

    expect($marketingPrefs)->toContain('mail')
        ->and($marketingPrefs)->toContain('database')
        ->and($marketingPrefs)->toContain('sms')
        ->and($marketingPrefs)->toHaveCount(3);
});

it('returns default channels for notification type without user preferences', function () {
    $user = $this->userClass::create([
        'name' => 'Bob',
        'email' => 'bob@example.com',
    ]);

    // Clear all preferences to test defaults
    UserNotificationPreference::where('user_id', $user->id)->delete();

    $channels = $user->getActiveChannelsFor('user.logged');

    expect($channels)->toBe(['mail']);
});

it('returns empty array for notification types not in enabled_notifications', function () {
    $user = $this->userClass::create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
    ]);

    // Clear preferences
    UserNotificationPreference::where('user_id', $user->id)->delete();

    // Type not in config should return empty
    $channels = $user->getActiveChannelsFor('unknown.type');

    expect($channels)->toBe([]);
});

it('returns user preferences when they exist', function () {
    $user = $this->userClass::create([
        'name' => 'Charlie',
        'email' => 'charlie@example.com',
    ]);

    // User customizes preferences
    $user->disableNotificationChannel('user.logged', 'mail');
    $user->enableNotificationChannel('user.logged', 'sms');

    $channels = $user->getActiveChannelsFor('user.logged');

    expect($channels)->toContain('sms')
        ->and($channels)->not->toContain('mail');
});
