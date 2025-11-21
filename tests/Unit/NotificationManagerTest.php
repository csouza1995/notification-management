<?php

use Csouza\NotificationManagement\Notifications\UserLoggedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = new class
    {
        public $id = 1;

        public $name = 'John Doe';

        public function getKey()
        {
            return $this->id;
        }

        public function getActiveChannelsFor(string $type): array
        {
            return ['mail', 'database'];
        }

        public function notify($notification)
        {
            // Mock notify method
        }
    };

    // Set config for testing
    config(['notification-management.notification_types.user.logged' => UserLoggedNotification::class]);
    config(['notification-management.defaults.log_notifications' => false]);
});

it('can send notification by type from config', function () {
    $manager = app(\Csouza\NotificationManagement\Managers\NotificationManager::class);

    $data = [
        'ip' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'location' => 'São Paulo',
        'logged_at' => now()->toDateTimeString(),
    ];

    // Should not throw exception
    expect(fn () => $manager->sendByType($this->user, 'user.logged', $data))->not->toThrow(\Exception::class);
});

it('throws exception for unregistered notification type', function () {
    $manager = app(\Csouza\NotificationManagement\Managers\NotificationManager::class);

    $manager->sendByType($this->user, 'invalid.type', []);
})->throws(\InvalidArgumentException::class, "Notification type 'invalid.type' is not registered in config.");

it('throws exception when type is mapped to string instead of class', function () {
    config(['notification-management.notification_types.test.type' => 'Just a description']);

    $manager = app(\Csouza\NotificationManagement\Managers\NotificationManager::class);

    $manager->sendByType($this->user, 'test.type', []);
})->throws(\InvalidArgumentException::class);
