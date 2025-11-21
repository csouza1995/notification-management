<?php

namespace Csouza\NotificationManagement\Listeners;

use Csouza\NotificationManagement\Facades\NotificationManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class SendUserLoggedNotification
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Prepare login details
        $loginDetails = [
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'location' => $this->getLocationFromIp(Request::ip()),
            'logged_at' => now()->toDateTimeString(),
        ];

        // Send notification respecting user preferences
        // Uses the notification class mapped in config: notification_types.user.logged
        NotificationManager::sendByType($user, 'user.logged', $loginDetails);
    }

    /**
     * Get approximate location from IP (placeholder - integrate with service if needed)
     */
    protected function getLocationFromIp(string $ip): ?string
    {
        // In production, integrate with a service like:
        // - ipapi.co
        // - ip-api.com
        // - MaxMind GeoIP2

        // For now, return null
        return null;
    }
}
