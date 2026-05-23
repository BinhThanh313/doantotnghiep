<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = ['carrier_id', 'province', 'region', 'fee', 'estimated_days'];

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }
}
