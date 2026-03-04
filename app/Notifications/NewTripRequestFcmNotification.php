<?php

namespace App\Notifications;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewTripRequestFcmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Trip $trip,
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

        $title = __('notifications.new_trip_request');
        $body = __('Searching');

        app()->setLocale($previousLocale);

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body,
        )))->data([
            'type' => 'new_trip_request',
            'trip_id' => (string) $this->trip->id,
        ]);
    }

    protected function getLocale(object $notifiable): string
    {
        return $notifiable instanceof User && $notifiable->lang == 1 ? 'ar' : 'en';
    }
}
