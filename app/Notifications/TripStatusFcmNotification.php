<?php

namespace App\Notifications;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class TripStatusFcmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Trip $trip,
        public TripStatus $status,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): FcmMessage
    {
        $locale = $this->getLocale($notifiable);
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        $statusLabel = $this->status->label();
        $title = __('notifications.trip_status', ['status' => $statusLabel]);

        app()->setLocale($previousLocale);

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $statusLabel,
        )))->data([
            'type' => 'trip_status',
            'trip_id' => (string) $this->trip->id,
            'status' => (string) $this->status->value,
        ]);
    }

    protected function getLocale(object $notifiable): string
    {
        return $notifiable instanceof User && $notifiable->lang == 1 ? 'ar' : 'en';
    }
}
