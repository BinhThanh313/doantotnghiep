<?php
namespace App\Http\Controllers;

use App\Models\FlashSale;

class FlashSalePageController extends Controller
{
    public function index()
    {
        $flashSales = FlashSale::running()
            ->with(['activeItems.product:id,name,image,price,original_price,stock,category_id'])
            ->orderBy('starts_at', 'desc')
            ->get();

        $upcomingSales = FlashSale::where('is_active', true)
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return view('shop.flash-sale', compact('flashSales', 'upcomingSales'));
    }
}