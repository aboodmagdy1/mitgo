<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\CustomNotification;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\ChatMessageFcmNotification;
use App\Notifications\NewTripRequestFcmNotification;
use App\Notifications\TripStatusFcmNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Message;

class NotificationService
{
    /**
     * Send FCM notification when trip status changes.
     * Only notifies the recipient (the party who did not initiate the change):
     * - Driver-initiated (accepted, arrived, started, ended, cancelled by driver) → notify client
     * - Rider-initiated (cancelled by rider) → notify driver
     * - System-initiated (no driver found, cancelled by system) → notify client
     *
     * @param  string  $initiatedBy  'driver'|'rider'|'system'
     */
    public function sendTripStatusNotification(Trip $trip, TripStatus $newStatus, string $initiatedBy = 'driver'): void
    {
        try {
            $notification = new TripStatusFcmNotification($trip, $newStatus);

            // Driver, system, or unknown initiated → notify client only
            if (in_array($initiatedBy, ['driver', 'system', 'unknown'])) {
                if ($trip->user_id) {
                    $client = $trip->user;
                    if ($client && $client->getFCMTokens()) {
                        $client->notify($notification);
                    }
                }
                return;
            }

            // Rider initiated → notify driver only
            if ($initiatedBy === 'rider' && $trip->driver_id) {
                $driver = $trip->driver?->user;
                if ($driver && $driver->getFCMTokens()) {
                    $driver->notify($notification);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send trip status FCM notification', [
                'trip_id' => $trip->id,
                'status' => $newStatus->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send FCM notification for new chat message.
     * Notifies the recipient (the other user in the conversation).
     */
    public function sendChatMessageNotification(Message $message): void
    {
        try {
            $conversation = $message->conversation;
            if (!$conversation) {
                return;
            }

            $recipient = $conversation->getOtherUser($message->sender_id);
            if (!$recipient || !$recipient->getFCMTokens()) {
                return;
            }

            $senderName = $message->sender?->name ?? __('User');
            $recipient->notify(new ChatMessageFcmNotification($message, $senderName));
        } catch (\Throwable $e) {
            Log::error('Failed to send chat message FCM notification', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send FCM notification for new trip request to driver.
     */
    public function sendNewTripRequestNotification(Trip $trip, User $driver): void
    {
        try {
            if (!$driver->getFCMTokens()) {
                return;
            }

            $driver->notify(new NewTripRequestFcmNotification($trip));
        } catch (\Throwable $e) {
            Log::error('Failed to send new trip request FCM notification', [
                'trip_id' => $trip->id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user notifications in current locale
     */
    public function getUserNotifications(int $userId, int $perPage = 10)
    {
        return CustomNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(int $userId, array $notificationIds): int
    {
        return CustomNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->whereIn('id', $notificationIds)
            ->update(['is_read' => true]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(int $userId): int
    {
        return CustomNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->where('is_read', false)
            ->count();
    }
}
