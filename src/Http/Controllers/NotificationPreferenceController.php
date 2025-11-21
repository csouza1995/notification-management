<?php

namespace Csouza\NotificationManagement\Http\Controllers;

use Csouza\NotificationManagement\Managers\NotificationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        protected NotificationManager $notificationManager
    ) {}

    /**
     * Get all notification preferences for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $this->notificationManager->getUserPreferences($user);

        return response()->json([
            'data' => $preferences,
        ]);
    }

    /**
     * Update notification preferences for the authenticated user
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'required|array',
            'preferences.*.*' => 'required|boolean',
        ]);

        $user = $request->user();
        $this->notificationManager->setUserPreferences($user, $validated['preferences']);

        return response()->json([
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Enable a specific channel for a notification type
     */
    public function enable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_type' => 'required|string',
            'channel' => 'required|string',
        ]);

        $user = $request->user();
        $this->notificationManager->enableChannel(
            $user,
            $validated['notification_type'],
            $validated['channel']
        );

        return response()->json([
            'message' => 'Channel enabled successfully',
        ]);
    }

    /**
     * Disable a specific channel for a notification type
     */
    public function disable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_type' => 'required|string',
            'channel' => 'required|string',
        ]);

        $user = $request->user();
        $this->notificationManager->disableChannel(
            $user,
            $validated['notification_type'],
            $validated['channel']
        );

        return response()->json([
            'message' => 'Channel disabled successfully',
        ]);
    }

    /**
     * Get available channels
     */
    public function channels(): JsonResponse
    {
        $nativeChannels = ['mail', 'database', 'broadcast'];
        $customChannels = array_keys(config('notification-management.channels', []));

        return response()->json([
            'data' => [
                'native' => $nativeChannels,
                'custom' => $customChannels,
                'all' => array_merge($nativeChannels, $customChannels),
            ],
        ]);
    }

    /**
     * Get available notification types
     */
    public function types(): JsonResponse
    {
        $types = config('notification-management.notification_types', []);

        return response()->json([
            'data' => $types,
        ]);
    }

    /**
     * Get notification history for the authenticated user
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $notificationType = $request->query('notification_type');
        $limit = (int) $request->query('limit', 50);

        $history = $this->notificationManager->getNotificationHistory(
            $user,
            $notificationType,
            $limit
        );

        return response()->json([
            'data' => $history,
        ]);
    }
}
