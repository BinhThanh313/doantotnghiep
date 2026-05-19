<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    // Cấp phép cho các trường này được lưu tự động (Mass Assignment)
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price'
    ];

    // (Tùy chọn) Thêm hàm này để liên kết ngược lại với bảng Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}