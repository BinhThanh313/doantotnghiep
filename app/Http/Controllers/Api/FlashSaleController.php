<?php
// app/Http/Controllers/Api/FlashSaleController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;

class FlashSaleController extends Controller
{
    // GET /api/flash-sales/current  — Flash Sale đang chạy
    public function current()
    {
        $sale = FlashSale::running()
            ->with(['items' => function ($q) {
                $q->where('is_active', true)
                  ->with('product:id,name,image,price,original_price');
            }])
            ->latest('starts_at')
            ->first();

        if (!$sale) {
            return response()->json(['data' => null]);
        }

        $sale->seconds_remaining = $sale->seconds_remaining;

        return response()->json(['data' => $sale]);
    }
}