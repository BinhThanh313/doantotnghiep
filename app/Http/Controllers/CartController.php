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

    // Lấy discount từ session nếu có voucher
    if (session('applied_voucher')) {
        $voucher = \App\Models\Voucher::where('code', session('applied_voucher'))->first();
        if ($voucher) {
            $discount = $voucher->calculateDiscount($total);
        }
    }

    if ($request->ajax() || $request->get('ajax')) {
        return view('shop.cart-items', compact('cart'))->render() . 
               view('shop.cart-summary', compact('cart', 'total', 'discount'))->render();
    }

    return view('shop.cart', compact('cart', 'total', 'discount'));
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

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => count($cart)
        ]);
    }

    public function update(Request $request, $id)
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            $action = $request->action ?? 'plus';
            
            if ($action === 'plus') {
                $cart[$id]['quantity'] += 1;
            } else {
                $cart[$id]['quantity'] = max(1, $cart[$id]['quantity'] - 1);
            }

            session()->put('cart', $cart);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
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