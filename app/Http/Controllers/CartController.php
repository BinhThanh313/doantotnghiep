<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getCart()
    {
        return session()->get('cart', []);
    }

    public function index(Request $request)
{
    $cart = session()->get('cart', []);
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
        return view('shop.cart-items', compact('cart'))->render() . 
               view('shop.cart-summary', compact('cart', 'total', 'discount', 'appliedVouchers'))->render();
    }

    return view('shop.cart', compact('cart', 'total', 'discount', 'appliedVouchers'));
}

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product  = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;
        $cart     = $this->getCart();

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] += $quantity;
        } else {
            $cart[$product->id] = [
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => $product->price,
                'image'    => $product->image,
                'quantity' => $quantity,
            ];
        }

        session()->put('cart', $cart);

        // Form submit bình thường (không phải AJAX) -> quay lại trang trước kèm thông báo,
        // tránh hiện thẳng JSON ra màn hình. Chỉ trả JSON khi gọi bằng fetch/AJAX (vd: nút .add-to-cart ở trang shop).
        if (! ($request->ajax() || $request->wantsJson())) {
            return redirect()->back()->with('cart_message', 'Đã thêm "' . $product->name . '" vào giỏ hàng!');
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => count($cart)
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, $id)
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            $action = $request->action ?? 'plus';
            
            if ($action === 'plus') {
                // ✅ SỬA: Giới hạn tối đa để tránh abuse
                $cart[$id]['quantity'] = min(99, $cart[$id]['quantity'] + 1);
            } elseif ($action === 'minus') {
                $cart[$id]['quantity'] = max(1, $cart[$id]['quantity'] - 1);
            }
            // ✅ SỬA: Bỏ qua các action không hợp lệ thay vì xử lý silently

            session()->put('cart', $cart);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'quantity' => $cart[$id]['quantity'] ?? 0,
            ]);
        }

        return redirect()->route('cart.index');
    }

    public function remove($id, Request $request)
    {
        $cart = $this->getCart();
        unset($cart[$id]);
        session()->put('cart', $cart);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cart.index');
    }
}