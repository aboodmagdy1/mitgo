<?php

namespace App\Listeners;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Chat\Events\MessageSent;

class SendChatMessageFcmNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function handle(MessageSent $event): void
    {
        $this->notificationService->sendChatMessageNotification($event->message);
    }
}
