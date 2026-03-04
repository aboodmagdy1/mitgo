<?php

namespace Modules\Chat\Services;

use Modules\Chat\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use App\Services\BaseService;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Enums\MessageType;
use App\Services\OfferService;
// use App\Notifications\ChatMessageNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class MessageService extends BaseService
{
    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
        parent::__construct($message);
    }


    public function createWithBusinessLogic(array $data)
    {

        $message = $this->message->create([
            'conversation_id' => $data['conversation_id'],
            'sender_id' => $data['sender_id'],
            'content' => $data['content'] ?? '',
        ]);

        $this->afterCreate($message);
        return $message;
    }



    protected function afterCreate(Message $message): void
    {
        // Update conversation's last message
        if ($message->conversation) {
            $message->conversation->last_message_id = $message->id;
            $message->conversation->save();
        }

        // Broadcast event
        broadcast(new MessageSent($message));

    }
}