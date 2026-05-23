<?php
// ========== VoucherUsage.php ==========
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    protected $table = 'voucher_usages';
    protected $fillable = ['voucher_id', 'user_id', 'order_id'];

    public function voucher() { return $this->belongsTo(Voucher::class); }
    public function user()    { return $this->belongsTo(User::class); }
    public function order()   { return $this->belongsTo(Order::class); }
}
