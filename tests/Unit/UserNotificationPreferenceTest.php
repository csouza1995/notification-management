<?php

use Csouza\NotificationManagement\Models\UserNotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a user notification preference', function () {
    $preference = UserNotificationPreference::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'is_enabled' => true,
    ]);

    expect($preference)->toBeInstanceOf(UserNotificationPreference::class)
        ->and($preference->channel_name)->toBe('mail')
        ->and($preference->notification_type)->toBe('order.shipped')
        ->and($preference->is_enabled)->toBeTrue();
});

it('can enable and disable preferences', function () {
    $preference = UserNotificationPreference::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'is_enabled' => true,
    ]);

    expect($preference->isEnabled())->toBeTrue();

    $preference->disable();
    expect($preference->fresh()->isEnabled())->toBeFalse();

    $preference->enable();
    expect($preference->fresh()->isEnabled())->toBeTrue();
});

it('can scope by notification type', function () {
    UserNotificationPreference::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'is_enabled' => true,
    ]);

    UserNotificationPreference::create([
        'user_id' => 1,
        'channel_name' => 'sms',
        'notification_type' => 'order.delivered',
        'is_enabled' => true,
    ]);

    $preferences = UserNotificationPreference::forNotificationType('order.shipped')->get();

    expect($preferences)->toHaveCount(1)
        ->and($preferences->first()->notification_type)->toBe('order.shipped');
});

it('can scope by enabled status', function () {
    UserNotificationPreference::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'is_enabled' => true,
    ]);

    UserNotificationPreference::create([
        'user_id' => 1,
        'channel_name' => 'sms',
        'notification_type' => 'order.shipped',
        'is_enabled' => false,
    ]);

    $enabled = UserNotificationPreference::enabled()->get();

    expect($enabled)->toHaveCount(1)
        ->and($enabled->first()->channel_name)->toBe('mail');
});
