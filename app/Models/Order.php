<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    protected $fillable = ['user_id','customer_name','customer_email',
                           'customer_phone','address','total_amount',
                           'status','payment_method','notes'];
    
    public function items() {
        return $this->hasMany(OrderItem::class);
    }
}