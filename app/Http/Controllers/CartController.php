<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Giỏ hàng được lưu bền vững trong bảng cart_items (gắn với user_id),
 * KHÔNG dùng session — vì toàn bộ route /cart/* đều yêu cầu đăng nhập
 * (xem middleware('auth') trong routes/web.php). Nhờ vậy giỏ hàng
 * không bị mất khi người dùng đăng xuất rồi đăng nhập lại.
 *
 * Hỗ trợ biến thể (màu/size...): 1 sản phẩm có thể xuất hiện nhiều dòng
 * trong giỏ nếu khách chọn các biến thể khác nhau (VD: 1 dòng "Áo - Đỏ",
 * 1 dòng "Áo - Xanh"). Khoá của mảng $cart trả về giờ là ID của chính
 * dòng cart_items (không còn là product_id) để phân biệt được các dòng
 * cùng sản phẩm khác biến thể — các nút +/-/xoá ở view dùng key này.
 */
class CartController extends Controller
{
    /**
     * Trả về giỏ hàng dưới dạng mảng thống nhất, key = cart_items.id.
     * [cart_item_id => ['id'=>product_id,'variant_id','name','variant_name','price','image','quantity','stock']]
     */
    private function getCart()
    {
        return CartItem::with(['product.activeFlashSaleItem', 'variant'])
            ->where('user_id', Auth::id())
            ->get()
            ->filter(fn($item) => $item->product !== null)
            ->mapWithKeys(function ($item) {
                $product = $item->product;
                $variant = $item->variant;

                // Ưu tiên giá/ảnh của biến thể nếu có set riêng, ngược lại
                // dùng giá/ảnh của sản phẩm gốc (kể cả giá Flash Sale).
                $price = $variant && $variant->price !== null
                    ? (float) $variant->price
                    : $product->effective_price;

                $image = $variant && $variant->image ? $variant->image : $product->image;
                $stock = $variant ? $variant->stock : $product->stock;

                return [$item->id => [
                    'id'           => $product->id,
                    'variant_id'   => $variant?->id,
                    'name'         => $product->name,
                    'variant_name' => $variant?->name,
                    'price'        => $price,
                    'image'        => $image,
                    'quantity'     => $item->quantity,
                    'stock'        => $stock,
                ]];
            })
            ->toArray();
    }

    public function index(Request $request)
    {
        $fullCart = $this->getCart();
        $cartForSummary = $fullCart;

        // Nếu có truyền danh sách ID được chọn (từ file cart.blade.php gửi lên AJAX)
        if ($request->has('selected_ids')) {
            $selectedIds = array_filter(explode(',', $request->selected_ids));
            if (!empty($selectedIds)) {
                $selectedIds = array_map('intval', $selectedIds);
                $cartForSummary = array_filter($fullCart, fn($item, $key) => in_array((int) $key, $selectedIds), ARRAY_FILTER_USE_BOTH);
            } else {
                $cartForSummary = [];
            }
        }

        $total = 0;
        $discount = 0;

        foreach ($cartForSummary as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Lấy discount từ combo
        $comboDiscountResult = \App\Models\ProductCombo::calculateCartDiscount($cartForSummary);
        $discount += $comboDiscountResult['amount'];
        $comboDetails = $comboDiscountResult['details'];

        // Lấy discount từ session nếu có voucher (hỗ trợ nhiều mã cùng lúc)
        $appliedVoucherCodes = session('applied_vouchers', []);
        $appliedVouchers = [];
        
        // Thêm các combo vào danh sách voucher để hiển thị ở view
        foreach ($comboDetails as $cd) {
            $appliedVouchers[] = [
                'code' => $cd['name'],
                'discount' => $cd['discount_amount']
            ];
        }

        if (!empty($appliedVoucherCodes) && $total > 0) {
            $vouchers = \App\Models\Voucher::whereIn('code', $appliedVoucherCodes)
                ->get()
                ->sortBy(fn($v) => array_search($v->code, $appliedVoucherCodes))
                ->values();

            $result = \App\Models\Voucher::calculateStackedDiscount($vouchers, $total - $comboDiscountResult['amount']);
            $discount += $result['total'];
            $appliedVouchers = array_merge($appliedVouchers, $result['breakdown']);
        }

        if ($request->ajax() || $request->get('ajax')) {
            return response()->json([
                'cart_html'    => view('shop.cart-items', ['cart' => $fullCart])->render(),
                'summary_html' => view('shop.cart-summary', [
                    'cart' => $cartForSummary, 
                    'total' => $total, 
                    'discount' => $discount, 
                    'appliedVouchers' => $appliedVouchers
                ])->render(),
            ]);
        }

        return view('shop.cart', [
            'cart' => $fullCart, 
            'total' => $total, 
            'discount' => $discount, 
            'appliedVouchers' => $appliedVouchers
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $product  = Product::with('activeFlashSaleItem')->findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        $variant = null;
        if ($request->filled('variant_id')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('is_active', true)
                ->find($request->variant_id);

            if (!$variant) {
                $message = 'Biến thể sản phẩm không hợp lệ hoặc đã ngừng bán.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return redirect()->back()->with('error', $message);
            }
        }

        // Mỗi dòng cart_items ứng với 1 cặp (product_id, variant_id) —
        // whereNull khi không có biến thể để không bị nhầm với các biến
        // thể khác (MySQL coi NULL != NULL nên phải so sánh tường minh).
        $item = CartItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->when($variant, fn($q) => $q->where('variant_id', $variant->id))
            ->when(!$variant, fn($q) => $q->whereNull('variant_id'))
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
        } else {
            CartItem::create([
                'user_id'    => Auth::id(),
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity'   => $quantity,
            ]);
        }

        $cartCount = CartItem::where('user_id', Auth::id())->count();
        $label = $product->name . ($variant ? " ({$variant->name})" : '');

        // Form submit bình thường (không phải AJAX) -> quay lại trang trước kèm thông báo,
        // tránh hiện thẳng JSON ra màn hình. Chỉ trả JSON khi gọi bằng fetch/AJAX (vd: nút .add-to-cart ở trang shop).
        if (! ($request->ajax() || $request->wantsJson())) {
            return redirect()->back()->with('cart_message', 'Đã thêm "' . $label . '" vào giỏ hàng!');
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => $cartCount
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * $id giờ là ID của dòng cart_items (không còn là product_id) để có
     * thể sửa đúng dòng khi 1 sản phẩm có nhiều biến thể trong giỏ.
     */
    public function update(Request $request, $id)
    {
        $action = $request->action ?? 'plus';
        $newQuantity = 0;

        $item = CartItem::where('user_id', Auth::id())
            ->where('id', $id)
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

    /**
     * $id là ID của dòng cart_items (xem ghi chú ở update()).
     */
    public function remove($id, Request $request)
    {
        CartItem::where('user_id', Auth::id())->where('id', $id)->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cart.index');
    }
}