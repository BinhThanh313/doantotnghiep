<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSimilarity extends Model
{
    protected $fillable = ['product_id', 'similar_product_id', 'score'];

    protected $casts = [
        'score' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function similarProduct()
    {
        return $this->belongsTo(Product::class, 'similar_product_id');
    }
}