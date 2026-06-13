<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ShippingCarrier;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\BankTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống!');
        }

        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $carriers = ShippingCarrier::where('is_active', true)->get();

        return view('shop.checkout', compact('cart', 'subtotal', 'carriers'));
    }

    public function applyVoucher(Request $request)
    {
        $request->validate([
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', strtoupper($request->code))->first();

        if (!$voucher || !$voucher->isValid()) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'], 422);
        }

        if (Auth::check() && $voucher->hasBeenUsedByUser(Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Bạn đã sử dụng mã này rồi'], 422);
        }

        $discount = $voucher->calculateDiscount($request->amount);

        if ($discount <= 0) {
            return response()->json(['success' => false, 'message' => "Đơn hàng tối thiểu " . number_format($voucher->min_amount, 0, ',', '.') . "đ để sử dụng mã này"], 422);
        }

        session(['applied_voucher' => $voucher->code]);

        return response()->json([
            'success'      => true,
            'discount'     => $discount,
            'voucher_id'   => $voucher->id,
            'voucher_name' => $voucher->name ?? $voucher->code,
            'message'      => 'Áp dụng mã thành công! Giảm ' . number_format($discount, 0, ',', '.') . 'đ',
        ]);
    }

    public function calculateShipping(Request $request)
    {
        $request->validate([
            'province'   => 'required|string',
            'carrier_id' => 'required|exists:shipping_carriers,id',
        ]);

        $carrier = ShippingCarrier::with('zones')->findOrFail($request->carrier_id);
        $zone    = $carrier->zones()->where('province', $request->province)->first();
        $fee     = $zone ? $zone->fee : $carrier->base_fee;
        $days    = $zone?->estimated_days ?? 3;

        return response()->json([
            'fee'            => $fee,
            'estimated_days' => $days,
            'message'        => "Phí vận chuyển: " . number_format($fee, 0, ',', '.') . "đ (dự kiến {$days} ngày)",
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'email'          => 'required|email',
            'province'       => 'required|string|max:100',
            'city'           => 'required|string|max:100',
            'address'        => 'required|string|max:255',
            'payment_method' => 'required|in:cod,bank',
            'carrier_id'     => 'nullable|exists:shipping_carriers,id',
            'voucher_code'   => 'nullable|string',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống!');
        }

        try {
            $order = DB::transaction(function () use ($request, $cart) {

                // 1. Kiểm tra tồn kho
                foreach ($cart as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['id']);
                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Sản phẩm '{$product->name}' chỉ còn {$product->stock} trong kho.");
                    }
                }

                // 2. Tính subtotal
                $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']);

                // 3. Tính shipping fee
                $shippingFee = 0;
                if ($request->filled('carrier_id')) {
                    $carrier     = ShippingCarrier::with('zones')->find($request->carrier_id);
                    $zone        = $carrier?->zones()->where('province', $request->province)->first();
                    $shippingFee = $zone ? $zone->fee : ($carrier?->base_fee ?? 0);
                }

                // 4. Áp dụng voucher
                $discountAmount = 0;
                $voucher        = null;
                $voucherCode    = $request->voucher_code ?? session('applied_voucher');

                if ($voucherCode) {
                    $voucher = Voucher::where('code', strtoupper($voucherCode))
                                     ->lockForUpdate()
                                     ->first();

                    if ($voucher && $voucher->isValid()) {
                        if ($voucher->max_uses === null || $voucher->used_count < $voucher->max_uses) {
                            $discountAmount = $voucher->calculateDiscount($subtotal);
                            if ($discountAmount > 0) {
                                $voucher->increment('used_count');
                            }
                        }
                    }
                }

                // 5. Tạo Order
                $order = Order::create([
                    'user_id'         => Auth::id(),
                    'customer_name'   => $request->first_name . ' ' . $request->last_name,
                    'customer_email'  => $request->email,
                    'customer_phone'  => $request->phone,
                    'province'        => $request->province,
                    'address'         => $request->address . ', ' . $request->city,
                    'total_amount'    => $subtotal,
                    'shipping_fee'    => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'payment_method'  => $request->payment_method,
                    'notes'           => $request->notes,
                    'status'          => 'pending',
                    'payment_status'  => 'unpaid',
                    'tracking_number' => Order::generateTrackingNumber(),
                ]);

                // 6. Tạo OrderItems + Trừ kho
                foreach ($cart as $item) {
                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $item['id'],
                        'product_name' => $item['name'],
                        'quantity'     => $item['quantity'],
                        'price'        => $item['price'],
                    ]);

                    Product::find($item['id'])->decreaseStock($item['quantity'], $order->id);
                }

                // 7. Lưu voucher usage
                if ($voucher && $discountAmount > 0) {
                    $order->vouchers()->attach($voucher->id, ['discount_amount' => $discountAmount]);

                    if (Auth::check()) {
                        VoucherUsage::create([
                            'voucher_id' => $voucher->id,
                            'user_id'    => Auth::id(),
                            'order_id'   => $order->id,
                        ]);
                    }
                }

                // 8. Tạo Shipment
                if ($request->filled('carrier_id')) {
                    Shipment::create([
                        'order_id'           => $order->id,
                        'carrier_id'         => $request->carrier_id,
                        'shipping_fee'       => $shippingFee,
                        'status'             => 'pending',
                        'estimated_delivery' => now()->addDays(3),
                    ]);
                }

                // 9. Tạo Payment
                Payment::create([
                    'order_id'       => $order->id,
                    'amount'         => $subtotal + $shippingFee - $discountAmount,
                    'payment_method' => strtoupper($request->payment_method),
                    'status'         => 'pending',
                ]);

                // 10. Thông báo in-app
                if (Auth::check()) {
                    AppNotification::send(
                        Auth::id(),
                        'order_placed',
                        'Đặt hàng thành công!',
                        "Đơn hàng #{$order->tracking_number} đã được tạo. Chúng tôi sẽ liên hệ xác nhận sớm.",
                        $order->id,
                        'order'
                    );
                }

                return $order;
            });

            session()->forget(['cart', 'applied_voucher']);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Đặt hàng thành công!',
                    'order_id'     => $order->id,
                    'redirect_url' => route('checkout.success', ['id' => $order->id]),
                ]);
            }

            return redirect()->route('checkout.success', $order->id);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['error' => $e->getMessage()],
                ], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function success($id)
    {
        $order = Order::with('items', 'shipment.carrier', 'vouchers', 'payment')->findOrFail($id);

        $bankInfo = null;
        if (strtolower($order->payment_method) === 'bank' && $order->payment_status !== 'paid') {
            $bankService = app(BankTransferService::class);
            $bankInfo    = array_merge(
                $bankService->generateQrCode($order),
                $bankService->getBankInfo()
            );
        }

        return view('shop.checkout-success', compact('order', 'bankInfo'));
    }

    public function orderDetail($id)
    {
        $order = Order::with([
            'items.product',
            'payment',
            'shipment.carrier',
            'vouchers',
        ])->where(function ($q) use ($id) {
            $q->where('id', $id)
              ->where(function ($q2) {
                  $q2->where('user_id', Auth::id())
                     ->orWhereNull('user_id');
              });
        })->firstOrFail();

        $bankInfo = null;
        if (strtolower($order->payment_method) === 'bank' && $order->payment_status !== 'paid') {
            $bankService = app(BankTransferService::class);
            $bankInfo    = array_merge(
                $bankService->generateQrCode($order),
                $bankService->getBankInfo()
            );
        }

        return view('order-detail', compact('order', 'bankInfo'));
    }
}