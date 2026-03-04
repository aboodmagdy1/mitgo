<?php

namespace Modules\Chat\Services;

use App\Models\User;
use Modules\Chat\Models\Conversation;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Services\MessageService ;
use Modules\Chat\Models\Message;

class ConversationService extends BaseService
{
    protected $conversation;
    protected $messageService;

    public function __construct(Conversation $conversation, MessageService $messageService)
    {
        $this->conversation = $conversation;
        $this->messageService = $messageService;
        parent::__construct($conversation);
    }

    /**
     * Get conversations for the sidebar with last message and user data
     */
    public function getConversations($user_id){
        $conversations = Conversation::whereHas('users', function($query) use ($user_id) {
            $query->where('users.id', $user_id);
        })
        ->where('last_message_id', '!=', null)
        ->whereDoesntHave('users', function($query) use ($user_id) {
            // Exclude conversations with users that the current user has blocked
            $query->where('users.id', '!=', $user_id)
                  ->whereExists(function($subQuery) use ($user_id) {
                      $subQuery->select('id')
                               ->from('user_blocks')
                               ->whereColumn('user_blocks.blocked_id', 'users.id')
                               ->where('user_blocks.blocker_id', $user_id);
                  });
        })
        ->with([
            'users',
            'lastMessage.sender'
        ])
        ->orderByDesc(
            Message::select('created_at')
                ->whereColumn('messages.id', 'conversations.last_message_id')
                ->take(1)
        )
        ->get();

        return $conversations;
    }

    /**
     * Start a new conversation or get existing one
     */
    public function startConversation($data)
    {
        $current_user_id = Auth::id();
        $receiver_id = $data['receiver_id'];
        
        $conversation = Conversation::getOrCreate($current_user_id, $receiver_id);
        $this->afterCreate($conversation);
        return $conversation;
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages($conversation_id , $limit = 10){
        $conversation = $this->conversation->find($conversation_id);
        $messages = $conversation->messages()->orderBy('messages.created_at', 'desc')->paginate($limit);
        return $messages;
    }




    /**
     * Get unread message count for a conversation
     */
    public function getUnreadCount($conversation_id, $user_id)
    {
        return Message::where('conversation_id', $conversation_id)
            ->where('sender_id', '!=', $user_id)
            ->unread()
            ->count();
    }

    /**
     * Mark all messages in a conversation as read for a specific user
     */
    public function markConversationAsRead($conversation_id, $user_id)
    {
        Message::where('conversation_id', $conversation_id)
            ->where('sender_id', '!=', $user_id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    protected function afterCreate(Conversation $conversation): void
    {

    }
}