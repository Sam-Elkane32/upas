<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'attachment_url',
        'attachments',
        'is_read',
        'reply_to_id',
        'edited_at',
        'is_pinned',
        'created_at',
    ];

    protected $casts = [
        'is_read'      => 'boolean',
        'is_pinned'    => 'boolean',
        'created_at'   => 'datetime',
        'edited_at'    => 'datetime',
        'deleted_at'   => 'datetime',
        'attachments'  => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->withTrashed();
    }
}
