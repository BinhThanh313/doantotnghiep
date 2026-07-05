<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender', 'message', 'intent'];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}