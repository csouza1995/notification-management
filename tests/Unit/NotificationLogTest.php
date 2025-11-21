<?php

use Csouza\NotificationManagement\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a notification log', function () {
    $log = NotificationLog::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'status' => 'sent',
        'payload' => ['order_id' => 123],
        'sent_at' => now(),
    ]);

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->channel_name)->toBe('mail')
        ->and($log->status)->toBe('sent')
        ->and($log->payload)->toBe(['order_id' => 123]);
});

it('can mark log as sent', function () {
    $log = NotificationLog::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'status' => 'pending',
        'payload' => [],
    ]);

    $log->markAsSent();

    expect($log->fresh()->status)->toBe('sent')
        ->and($log->fresh()->sent_at)->not->toBeNull()
        ->and($log->fresh()->wasSent())->toBeTrue();
});

it('can mark log as failed', function () {
    $log = NotificationLog::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'status' => 'pending',
        'payload' => [],
    ]);

    $log->markAsFailed('SMTP connection failed');

    expect($log->fresh()->status)->toBe('failed')
        ->and($log->fresh()->error_message)->toBe('SMTP connection failed')
        ->and($log->fresh()->hasFailed())->toBeTrue();
});

it('can scope logs by status', function () {
    NotificationLog::create([
        'user_id' => 1,
        'channel_name' => 'mail',
        'notification_type' => 'order.shipped',
        'status' => 'sent',
        'payload' => [],
    ]);

    NotificationLog::create([
        'user_id' => 1,
        'channel_name' => 'sms',
        'notification_type' => 'order.shipped',
        'status' => 'failed',
        'payload' => [],
    ]);

    NotificationLog::create([
        'user_id' => 1,
        'channel_name' => 'database',
        'notification_type' => 'order.shipped',
        'status' => 'pending',
        'payload' => [],
    ]);

    expect(NotificationLog::sent()->count())->toBe(1)
        ->and(NotificationLog::failed()->count())->toBe(1)
        ->and(NotificationLog::pending()->count())->toBe(1);
});
