<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message',
        'related_id', 'related_type', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    /**
     * Tạo thông báo nhanh
     */
    public static function send(int $userId, string $type, string $title, string $message, ?int $relatedId = null, ?string $relatedType = null): void
    {
        self::create([
            'user_id'      => $userId,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'related_id'   => $relatedId,
            'related_type' => $relatedType,
        ]);
    }
}
