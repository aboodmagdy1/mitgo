<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Models\Message;
use App\Models\User;

class Conversation extends Model
{
    protected $fillable = ['last_message_id'];

    /**
     * Many-to-many relationship with users (but will only have 2 users for one-to-one)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user');
    }

    /**
     * Relationship with messages
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Relationship for the last message
     */
    public function lastMessage()
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    /**
     * Helper method to get the other user in the conversation
     */
    public function getOtherUser($currentUserId)
    {
        return $this->users()->where('users.id', '!=', $currentUserId)->first();
    }

    /**
     * Helper method to check if a user is part of this conversation
     */
    public function hasUser($userId)
    {
        return $this->users()->where('users.id', $userId)->exists();
    }

    /**
     * Helper method to get or create a conversation between two users
     */
    public static function getOrCreate($user1Id, $user2Id)
    {
        // Find existing conversation between these two users
        $conversation = self::whereHas('users', function($query) use ($user1Id) {
            $query->where('users.id', $user1Id);
        })->whereHas('users', function($query) use ($user2Id) {
            $query->where('users.id', $user2Id);
        })->whereDoesntHave('users', function($query) use ($user1Id, $user2Id) {
            $query->whereNotIn('users.id', [$user1Id, $user2Id]);
        })->first();

        if (!$conversation) {
            $conversation = self::create();
            // Attach both users to the conversation
            $conversation->users()->attach([$user1Id, $user2Id]);
        }

        return $conversation;
    }

    /**
     * Get display title for the conversation (other user's name)
     */
    public function getDisplayTitle($currentUserId)
    {
        $otherUser = $this->getOtherUser($currentUserId);
        return $otherUser ? $otherUser->name : 'Unknown User';
    }
}
