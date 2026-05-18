<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = ['type'];

    protected $casts = [
        'type' => 'string',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withTimestamps();
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Check whether a given user is a participant.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->conversationParticipants()
                    ->where('user_id', $userId)
                    ->exists();
    }

    /**
     * Count unread messages for a specific user (receiver side).
     */
    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
                    ->where('is_read', false)
                    ->where('sender_id', '!=', $userId)
                    ->count();
    }
}
