<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\CartItem;
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
    /**
     * Đọc giỏ hàng từ bảng cart_items (DB) thay vì session — đồng bộ với
     * CartController sau khi giỏ hàng được chuyển sang lưu bền vững theo user_id.
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
                    'price'    => $product->effective_price,
                    'image'    => $product->image,
                    'quantity' => $item->quantity,
                ]];
            })
            ->toArray();
    }

    public function index()
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống!');
        }

        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $carriers = ShippingCarrier::where('is_active', true)->get();

        return view('shop.checkout', compact('cart', 'subtotal', 'carriers'));
    }

    /**
     * Áp dụng THÊM một mã voucher vào danh sách mã đang áp dụng trong session.
     * Cho phép áp nhiều mã cùng lúc — mã mới được cộng dồn vào danh sách,
     * không thay thế các mã đã áp trước đó.
     */
    public function applyVoucher(Request $request)
    {
        $request->validate([
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $code         = strtoupper(trim($request->code));
        $appliedCodes = session('applied_vouchers', []);

        if (in_array($code, $appliedCodes, true)) {
            return response()->json(['success' => false, 'message' => 'Mã "' . $code . '" đã được áp dụng rồi'], 422);
        }

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher || !$voucher->isValid()) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'], 422);
        }

        if (Auth::check() && $voucher->hasBeenUsedByUser(Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Bạn đã sử dụng mã này rồi'], 422);
        }

        // Tính lại discount cho TOÀN BỘ danh sách (mã cũ + mã mới) theo đúng
        // thứ tự áp dụng, để đảm bảo mã mới còn tác dụng khi stack với các mã trước.
        $newAppliedCodes = [...$appliedCodes, $code];
        $vouchers        = $this->loadVouchersInOrder($newAppliedCodes);
        $result          = Voucher::calculateStackedDiscount($vouchers, $request->amount);

        $newVoucherEntry = collect($result['breakdown'])->firstWhere('code', $code);

        if (!$newVoucherEntry || $newVoucherEntry['discount'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Mã \"{$code}\" không áp dụng được (chưa đạt đơn tối thiểu "
                    . number_format($voucher->min_amount, 0, ',', '.')
                    . "đ, hoặc phần đơn hàng còn lại sau các mã khác không đủ điều kiện)",
            ], 422);
        }

        session(['applied_vouchers' => $newAppliedCodes]);

        return response()->json([
            'success'  => true,
            'message'  => 'Áp dụng mã "' . $code . '" thành công! Giảm thêm ' . number_format($newVoucherEntry['discount'], 0, ',', '.') . 'đ',
            'discount' => $result['total'],
            'vouchers' => $result['breakdown'],
        ]);
    }

    /**
     * Gỡ một mã voucher khỏi danh sách mã đang áp dụng, tính lại discount
     * cho các mã còn lại.
     */
    public function removeVoucher(Request $request)
    {
        $request->validate([
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $code         = strtoupper(trim($request->code));
        $appliedCodes = array_values(array_diff(session('applied_vouchers', []), [$code]));

        session(['applied_vouchers' => $appliedCodes]);

        $result = empty($appliedCodes)
            ? ['total' => 0, 'breakdown' => []]
            : Voucher::calculateStackedDiscount($this->loadVouchersInOrder($appliedCodes), $request->amount);

        return response()->json([
            'success'  => true,
            'discount' => $result['total'],
            'vouchers' => $result['breakdown'],
        ]);
    }

    /**
     * Lấy các Voucher theo đúng thứ tự mã trong $codes (whereIn không đảm bảo thứ tự).
     */
    private function loadVouchersInOrder(array $codes)
    {
        $codes = array_map('strtoupper', $codes);

        return Voucher::whereIn('code', $codes)
            ->get()
            ->sortBy(fn($v) => array_search($v->code, $codes))
            ->values();
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
            'carrier_id'      => 'nullable|exists:shipping_carriers,id',
            'voucher_codes'   => 'nullable|array',
            'voucher_codes.*' => 'string',
        ]);

        $cart = $this->getCart();
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

                // 4. Áp dụng voucher (hỗ trợ nhiều mã cùng lúc, tính tuần tự)
                $discountAmount  = 0;
                $appliedVouchers = []; // [['voucher' => Voucher, 'discount' => float], ...]
                $voucherCodes    = $request->input('voucher_codes') ?: session('applied_vouchers', []);
                $voucherCodes    = array_values(array_unique(array_map('strtoupper', $voucherCodes)));

                if (!empty($voucherCodes)) {
                    $vouchers = Voucher::whereIn('code', $voucherCodes)
                        ->lockForUpdate()
                        ->get()
                        ->sortBy(fn($v) => array_search($v->code, $voucherCodes))
                        ->values();

                    $remaining = $subtotal;

                    foreach ($vouchers as $voucher) {
                        if (!$voucher->isValid()) continue;
                        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) continue;
                        if (Auth::check() && $voucher->hasBeenUsedByUser(Auth::id())) continue;

                        $discount = min($voucher->calculateDiscount($remaining), $remaining);
                        if ($discount > 0) {
                            $discountAmount += $discount;
                            $remaining      -= $discount;
                            $voucher->increment('used_count');
                            $appliedVouchers[] = ['voucher' => $voucher, 'discount' => $discount];
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

                // 7. Lưu voucher usage (từng mã trong danh sách đã áp dụng)
                foreach ($appliedVouchers as $entry) {
                    $order->vouchers()->attach($entry['voucher']->id, ['discount_amount' => $entry['discount']]);

                    if (Auth::check()) {
                        VoucherUsage::create([
                            'voucher_id' => $entry['voucher']->id,
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

            CartItem::where('user_id', Auth::id())->delete();
            session()->forget('applied_vouchers');

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