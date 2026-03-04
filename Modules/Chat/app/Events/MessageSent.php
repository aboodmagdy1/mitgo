<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Models\Message;
use Modules\Chat\Transformers\MessageResource;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Message $message) {}

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        // Ensure conversation relation is available
        $this->message->loadMissing('conversation.users');

        $channels = [
            new Channel('conversations.'.$this->message->conversation_id),
        ];

        foreach ($this->message->conversation->users as $participant) {
            $channels[] = new Channel('user.'.$participant->id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender.roles']);
        return [
            'message' => (new MessageResource($this->message))->resolve(),
        ];
    }
}
