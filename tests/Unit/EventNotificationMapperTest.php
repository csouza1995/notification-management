<?php

use Csouza\NotificationManagement\Services\EventNotificationMapper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create users table
    \Illuminate\Support\Facades\Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
    });

    // Create a simple user model for testing
    $this->userClass = new class extends Model
    {
        use \Csouza\NotificationManagement\Traits\HasNotificationPreferences;

        protected $table = 'users';

        protected $fillable = ['name', 'email'];

        public $timestamps = false;
    };

    $this->user = $this->userClass::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Mock notification manager
    $this->notificationManager = Mockery::mock(\Csouza\NotificationManagement\Managers\NotificationManager::class);
    $this->mapper = new EventNotificationMapper($this->notificationManager);
});

it('extracts notifiable from simple property', function () {
    $event = new class
    {
        public $user;

        public function __construct()
        {
            $this->user = null; // Will be set in test
        }
    };
    $event->user = $this->user;

    $config = [
        'notification_type' => 'test.notification',
        'notifiable' => 'user',
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->once()
        ->with($this->user, 'test.notification', []);

    $this->mapper->handle($event, $config);
});

it('extracts notifiable using dot notation', function () {
    $order = new class
    {
        public $user;
    };
    $order->user = $this->user;

    $event = new class
    {
        public $order;
    };
    $event->order = $order;

    $config = [
        'notification_type' => 'order.shipped',
        'notifiable' => 'order.user',
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->once()
        ->with($this->user, 'order.shipped', []);

    $this->mapper->handle($event, $config);
});

it('extracts notifiable using closure', function () {
    $comment = new class
    {
        public $post;

        public function __construct()
        {
            $this->post = new class
            {
                public $author;
            };
        }
    };
    $comment->post->author = $this->user;

    $event = new class
    {
        public $comment;
    };
    $event->comment = $comment;

    $config = [
        'notification_type' => 'comment.posted',
        'notifiable' => fn ($event) => $event->comment->post->author,
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->once()
        ->with($this->user, 'comment.posted', []);

    $this->mapper->handle($event, $config);
});

it('handles multiple notifiables from collection', function () {
    $user2 = $this->userClass::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $event = new class
    {
        public $subscribers;
    };
    $event->subscribers = collect([$this->user, $user2]);

    $config = [
        'notification_type' => 'post.published',
        'notifiable' => 'subscribers',
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->twice() // Once for each user
        ->with(Mockery::on(fn ($user) => in_array($user->id, [$this->user->id, $user2->id])), 'post.published', []);

    $this->mapper->handle($event, $config);
});

it('extracts custom data using closure', function () {
    $event = new class
    {
        public $user;

        public $orderId = 123;

        public $trackingCode = 'ABC123';
    };
    $event->user = $this->user;

    $config = [
        'notification_type' => 'order.shipped',
        'notifiable' => 'user',
        'data' => fn ($event) => [
            'order_id' => $event->orderId,
            'tracking_code' => $event->trackingCode,
        ],
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->once()
        ->with($this->user, 'order.shipped', [
            'order_id' => 123,
            'tracking_code' => 'ABC123',
        ]);

    $this->mapper->handle($event, $config);
});

it('respects condition and skips notification when false', function () {
    $event = new class
    {
        public $user;

        public $attempts = 1;
    };
    $event->user = $this->user;

    $config = [
        'notification_type' => 'payment.failed',
        'notifiable' => 'user',
        'condition' => fn ($event) => $event->attempts >= 3,
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->never();

    $this->mapper->handle($event, $config);
});

it('sends notification when condition is true', function () {
    $event = new class
    {
        public $user;

        public $attempts = 3;
    };
    $event->user = $this->user;

    $config = [
        'notification_type' => 'payment.failed',
        'notifiable' => 'user',
        'condition' => fn ($event) => $event->attempts >= 3,
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->once()
        ->with($this->user, 'payment.failed', []);

    $this->mapper->handle($event, $config);
});

it('skips notification when enabled is false', function () {
    $event = new class
    {
        public $user;
    };
    $event->user = $this->user;

    $config = [
        'notification_type' => 'test.disabled',
        'notifiable' => 'user',
        'enabled' => false,
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->never();

    $this->mapper->handle($event, $config);
});

it('skips notification when notifiable is null', function () {
    $event = new class
    {
        public $user = null;
    };

    $config = [
        'notification_type' => 'test.notification',
        'notifiable' => 'user',
    ];

    $this->notificationManager
        ->shouldReceive('sendByType')
        ->never();

    $this->mapper->handle($event, $config);
});

it('throws exception when notification_type is missing', function () {
    $event = new class
    {
        public $user;
    };
    $event->user = $this->user;

    $config = [
        'notifiable' => 'user',
        // notification_type missing
    ];

    $this->mapper->handle($event, $config);
})->throws(\InvalidArgumentException::class, 'notification_type is required');
