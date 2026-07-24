<?php
namespace App\Http\Controllers;

use App\Models\FlashSale;

class FlashSalePageController extends Controller
{
    public function index()
    {
        $flashSale = \Illuminate\Support\Facades\Cache::remember('shop_flash_sale_running', 60, fn() => 
            FlashSale::running()
                ->with(['activeItems.product:id,name,image,price,original_price,stock,category_id'])
                ->latest('starts_at')
                ->first()
        );

        $upcomingSales = \Illuminate\Support\Facades\Cache::remember('shop_flash_sale_upcoming', 60, fn() => 
            FlashSale::where('is_active', true)
                ->where('starts_at', '>', now())
                ->orderBy('starts_at')
                ->limit(3)
                ->get()
        );

        return view('shop.flash-sale', compact('flashSale', 'upcomingSales'));
    }
}