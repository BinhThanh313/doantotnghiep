<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('product', 'user', 'images');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('is_visible')) {
            $query->where('is_visible', $request->is_visible);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show($id)
    {
        $review = Review::with([
            'product:id,name,price,image', 
            'user:id,name,email', 
            'images', 
            'order'
        ])->findOrFail($id);
        
        return response()->json($review);
    }

    /**
     * Admin toggle ẩn/hiện review
     */
    public function toggleVisibility($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_visible' => !$review->is_visible]);

        return response()->json([
            'message'    => $review->is_visible ? 'Đã hiện review' : 'Đã ẩn review',
            'is_visible' => $review->is_visible,
        ]);
    }

    public function destroy($id)
    {
        Review::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xóa đánh giá']);
    }
}
