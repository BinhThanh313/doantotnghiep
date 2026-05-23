<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $items = Wishlist::with('product.category', 'product.primaryImage')
                         ->where('user_id', Auth::id())
                         ->latest()
                         ->get();

        return response()->json($items);
    }

    public function toggle($productId)
    {
        Product::findOrFail($productId);

        $existing = Wishlist::where('user_id', Auth::id())
                            ->where('product_id', $productId)
                            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['wishlisted' => false, 'message' => 'Đã xóa khỏi yêu thích']);
        }

        Wishlist::create(['user_id' => Auth::id(), 'product_id' => $productId]);

        return response()->json(['wishlisted' => true, 'message' => 'Đã thêm vào yêu thích']);
    }

    public function destroy($productId)
    {
        Wishlist::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->delete();

        return response()->json(['message' => 'Đã xóa khỏi danh sách yêu thích']);
    }
}
