<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'order_id', 'rating',
        'title', 'comment', 'video_url', 'verified_purchase', 'helpful_count', 'is_visible',
    ];

    protected $casts = [
        'verified_purchase' => 'boolean',
        'is_visible'        => 'boolean',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function order()   { return $this->belongsTo(Order::class); }

    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function helpfulVotes()
    {
        return $this->hasMany(ReviewHelpful::class);
    }
}