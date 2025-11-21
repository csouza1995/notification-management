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
    };
});

it('can create user logged notification', function () {
    $loginDetails = [
        'ip' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'location' => 'São Paulo, Brazil',
        'logged_at' => now()->toDateTimeString(),
    ];

    $notification = new UserLoggedNotification($loginDetails);

    expect($notification)->toBeInstanceOf(UserLoggedNotification::class);
});

it('returns correct channels based on user preferences', function () {
    $notification = new UserLoggedNotification([
        'ip' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'location' => 'São Paulo, Brazil',
        'logged_at' => now()->toDateTimeString(),
    ]);

    $channels = $notification->via($this->user);

    expect($channels)->toBe(['mail', 'database']);
});

it('generates correct mail message', function () {
    $loginDetails = [
        'ip' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'location' => 'São Paulo, Brazil',
        'logged_at' => now()->toDateTimeString(),
    ];

    $notification = new UserLoggedNotification($loginDetails);
    $mailMessage = $notification->toMail($this->user);

    expect($mailMessage->subject)->toBe('New Login Detected')
        ->and($mailMessage->greeting)->toContain('Hello John Doe!');
});

it('generates correct database representation', function () {
    $loginDetails = [
        'ip' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'location' => 'São Paulo, Brazil',
        'logged_at' => now()->toDateTimeString(),
    ];

    $notification = new UserLoggedNotification($loginDetails);
    $data = $notification->toDatabase($this->user);

    expect($data)->toHaveKey('type')
        ->and($data['type'])->toBe('user.logged')
        ->and($data)->toHaveKey('ip')
        ->and($data['ip'])->toBe('192.168.1.1')
        ->and($data)->toHaveKey('user_agent')
        ->and($data)->toHaveKey('location');
});
