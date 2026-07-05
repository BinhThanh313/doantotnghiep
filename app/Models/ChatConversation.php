<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = ['user_id', 'session_token'];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}