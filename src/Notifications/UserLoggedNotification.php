<?php

namespace Csouza\NotificationManagement\Notifications;

use Csouza\NotificationManagement\Traits\UsesNotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserLoggedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesNotificationPreferences;

    /**
     * Notification type identifier
     */
    protected string $notificationType = 'user.logged';

    public function __construct(
        protected array $loginDetails
    ) {}

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Login Detected')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('We detected a new login to your account.')
            ->line('**Login Details:**')
            ->line('IP Address: '.$this->loginDetails['ip'])
            ->line('Browser: '.$this->loginDetails['user_agent'])
            ->line('Location: '.($this->loginDetails['location'] ?? 'Unknown'))
            ->line('Time: '.$this->loginDetails['logged_at'])
            ->line('If this wasn\'t you, please secure your account immediately.')
            ->action('View Account Activity', url('/account/security'));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'user.logged',
            'title' => 'New Login Detected',
            'message' => 'A new login was detected from '.$this->loginDetails['ip'],
            'ip' => $this->loginDetails['ip'],
            'user_agent' => $this->loginDetails['user_agent'],
            'location' => $this->loginDetails['location'] ?? null,
            'logged_at' => $this->loginDetails['logged_at'],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user.logged',
            'login_details' => $this->loginDetails,
        ];
    }
}
