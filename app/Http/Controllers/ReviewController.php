<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpful;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * Lấy reviews của một sản phẩm (public)
     */
    public function index(Request $request, $productId)
    {
        Product::findOrFail($productId);

        $query = Review::with('user:id,name', 'images')
                       ->where('product_id', $productId)
                       ->where('is_visible', true);

        // Lọc theo rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sắp xếp
        $sort = $request->get('sort', 'newest');
        match($sort) {
            'helpful' => $query->orderByDesc('helpful_count'),
            'highest' => $query->orderByDesc('rating'),
            'lowest'  => $query->orderBy('rating'),
            default   => $query->latest(),
        };

        $reviews = $query->paginate(10);

        // Thống kê rating
        $stats = Review::where('product_id', $productId)
                       ->where('is_visible', true)
                       ->selectRaw('rating, count(*) as count')
                       ->groupBy('rating')
                       ->pluck('count', 'rating');

        $avgRating = Review::where('product_id', $productId)
                           ->where('is_visible', true)
                           ->avg('rating');

        return response()->json([
            'reviews'    => $reviews,
            'stats'      => $stats,
            'avg_rating' => round($avgRating ?? 0, 1),
            'total'      => $reviews->total(),
        ]);
    }

    public function show($id)
    {
        $review = Review::with('user:id,name', 'images', 'product:id,name')->findOrFail($id);
        return response()->json($review);
    }   
    public function store(Request $request, $productId)
    {
        $user = Auth::user();
        Product::findOrFail($productId);

        // Kiểm tra đã review chưa
        $existing = Review::where('product_id', $productId)
                          ->where('user_id', $user->id)
                          ->first();
        if ($existing) {
            return response()->json(['message' => 'Bạn đã đánh giá sản phẩm này rồi'], 422);
        }

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'title'    => 'nullable|string|max:255',
            'comment'  => 'nullable|string|max:2000',
            'order_id' => 'nullable|exists:orders,id',
            'images.*' => 'nullable|image|max:2048',
        ]);

        // Kiểm tra verified purchase
        $verifiedPurchase = false;
        if ($data['order_id'] ?? null) {
            $verifiedPurchase = Order::where('id', $data['order_id'])
                                     ->where('user_id', $user->id)
                                     ->whereHas('items', fn($q) => $q->where('product_id', $productId))
                                     ->where('status', 'completed')
                                     ->exists();
        }

        $review = Review::create([
            'product_id'        => $productId,
            'user_id'           => $user->id,
            'order_id'          => $data['order_id'] ?? null,
            'rating'            => $data['rating'],
            'title'             => $data['title'] ?? null,
            'comment'           => $data['comment'] ?? null,
            'verified_purchase' => $verifiedPurchase,
        ]);

        // Upload ảnh nếu có
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image_url' => $path,
                ]);
            }
        }

        return response()->json($review->load('images', 'user:id,name'), 201);
    }

    /**
     * Đánh dấu review hữu ích
     */
    public function helpful($reviewId)
    {
        $user = Auth::user();
        $review = Review::findOrFail($reviewId);

        $existing = ReviewHelpful::where('review_id', $reviewId)
                                 ->where('user_id', $user->id)
                                 ->first();

        if ($existing) {
            $existing->delete();
            $review->decrement('helpful_count');
            return response()->json(['helpful' => false, 'count' => $review->helpful_count]);
        }

        ReviewHelpful::create(['review_id' => $reviewId, 'user_id' => $user->id]);
        $review->increment('helpful_count');

        return response()->json(['helpful' => true, 'count' => $review->helpful_count]);
    }
}
