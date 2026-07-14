<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Giỏ hàng được lưu bền vững trong bảng cart_items (gắn với user_id),
 * KHÔNG dùng session — vì toàn bộ route /cart/* đều yêu cầu đăng nhập
 * (xem middleware('auth') trong routes/web.php). Nhờ vậy giỏ hàng
 * không bị mất khi người dùng đăng xuất rồi đăng nhập lại.
 */
class CartController extends Controller
{
    /**
     * Trả về giỏ hàng dưới dạng mảng thống nhất (giữ nguyên shape cũ
     * để các view shop.cart / cart-items / cart-summary không cần sửa):
     * [product_id => ['id','name','price','image','quantity']]
     */
    private function getCart()
    {
        return CartItem::with('product.activeFlashSaleItem')
            ->where('user_id', Auth::id())
            ->get()
            ->filter(fn($item) => $item->product !== null)
            ->mapWithKeys(function ($item) {
                $product = $item->product;
                return [$product->id => [
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'price'    => $product->effective_price, // giá Flash Sale nếu đang chạy, ngược lại giá thường
                    'image'    => $product->image,
                    'quantity' => $item->quantity,
                ]];
            })
            ->toArray();
    }

    public function index(Request $request)
    {
        $cart = $this->getCart();
        $total = 0;
        $discount = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Lấy discount từ session nếu có voucher (hỗ trợ nhiều mã cùng lúc)
        $appliedVoucherCodes = session('applied_vouchers', []);
        $appliedVouchers = [];
        if (!empty($appliedVoucherCodes)) {
            $vouchers = \App\Models\Voucher::whereIn('code', $appliedVoucherCodes)
                ->get()
                ->sortBy(fn($v) => array_search($v->code, $appliedVoucherCodes))
                ->values();

            $result = \App\Models\Voucher::calculateStackedDiscount($vouchers, $total);
            $discount = $result['total'];
            $appliedVouchers = $result['breakdown'];
        }

        if ($request->ajax() || $request->get('ajax')) {
            return response()->json([
                'cart_html'    => view('shop.cart-items', compact('cart'))->render(),
                'summary_html' => view('shop.cart-summary', compact('cart', 'total', 'discount', 'appliedVouchers'))->render(),
            ]);
        }

        return view('shop.cart', compact('cart', 'total', 'discount', 'appliedVouchers'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product  = Product::with('activeFlashSaleItem')->findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        $item = CartItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
        } else {
            CartItem::create([
                'user_id'    => Auth::id(),
                'product_id' => $product->id,
                'quantity'   => $quantity,
            ]);
        }

        $cartCount = CartItem::where('user_id', Auth::id())->count();

        // Form submit bình thường (không phải AJAX) -> quay lại trang trước kèm thông báo,
        // tránh hiện thẳng JSON ra màn hình. Chỉ trả JSON khi gọi bằng fetch/AJAX (vd: nút .add-to-cart ở trang shop).
        if (! ($request->ajax() || $request->wantsJson())) {
            return redirect()->back()->with('cart_message', 'Đã thêm "' . $product->name . '" vào giỏ hàng!');
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => $cartCount
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, $id)
    {
        $action = $request->action ?? 'plus';
        $newQuantity = 0;

        $item = CartItem::where('user_id', Auth::id())
            ->where('product_id', $id)
            ->first();

        if ($item) {
            if ($action === 'plus') {
                // Giới hạn tối đa để tránh abuse
                $item->quantity = min(99, $item->quantity + 1);
            } elseif ($action === 'minus') {
                $item->quantity = max(1, $item->quantity - 1);
            }
            $item->save();
            $newQuantity = $item->quantity;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'quantity' => $newQuantity,
            ]);
        }

        return redirect()->route('cart.index');
    }

    public function remove($id, Request $request)
    {
        CartItem::where('user_id', Auth::id())->where('product_id', $id)->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cart.index');
    }
}
