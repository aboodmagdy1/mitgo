<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Chat\Models\Message;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ChatMessageFcmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public string $senderName,
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

        $title = __('notifications.new_chat_message', ['sender' => $this->senderName]);
        $body = \Illuminate\Support\Str::limit($this->message->content ?? '', 100);

        app()->setLocale($previousLocale);

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body,
        )))->data([
            'type' => 'chat_message',
            'message_id' => (string) $this->message->id,
            'conversation_id' => (string) $this->message->conversation_id,
        ]);
    }

    protected function getLocale(object $notifiable): string
    {
        return $notifiable instanceof User && $notifiable->lang == 1 ? 'ar' : 'en';
    }
}
