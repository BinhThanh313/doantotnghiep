<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    protected $fillable = ['category_id','name','slug','description',
                           'price','original_price','image','stock',
                           'is_new','is_active'];
    
    public function category() {
        return $this->belongsTo(Category::class);
    }
    
    public function reviews() {
        return $this->hasMany(Review::class);
    }
}