<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class TestFcmNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title = 'Test Notification',
        public string $body = 'FCM is working!',
    ) {}

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
            title: $this->title,
            body: $this->body,
        )))->data([
            'type' => 'test',
            'timestamp' => (string) now()->timestamp,
        ]);
    }
}
