<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')
                             ->with('error', 'Giỏ hàng đang trống!');
        }

        $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        return view('shop.checkout', compact('cart', 'total'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'email'          => 'required|email',
            'city'           => 'required|string|max:100',
            'address'        => 'required|string|max:255',
            'payment_method' => 'required|in:cod,bank',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        // Tạo đơn hàng
        $order = Order::create([
            'user_id'        => auth()->id(),
            'customer_name'  => $request->first_name . ' ' . $request->last_name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
            'address'        => $request->address . ', ' . $request->city,
            'total_amount'   => $total,
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
            'status'         => 'pending',
        ]);

        // Tạo order items
        foreach ($cart as $item) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $item['id'],
                'product_name' => $item['name'],
                'quantity'     => $item['quantity'],
                'price'        => $item['price'],
            ]);
        }

        // Xóa giỏ hàng
        session()->forget('cart');

        return redirect()->route('checkout.success', $order->id);
    }

    public function success($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('shop.checkout-success', compact('order'));
    }
}