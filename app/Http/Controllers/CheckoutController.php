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
use Illuminate\Support\Facades\Cache;
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
        return CartItem::with(['product.activeFlashSaleItem', 'variant'])
            ->where('user_id', Auth::id())
            ->get()
            ->filter(fn($item) => $item->product !== null)
            ->mapWithKeys(function ($item) {
                $product = $item->product;
                $variant = $item->variant;

                $price = $variant && $variant->price !== null
                    ? (float) $variant->price
                    : $product->effective_price;

                return [$item->id => [
                    'id'                => $product->id,
                    'variant_id'        => $variant?->id,
                    'name'              => $product->name,
                    'variant_name'      => $variant?->name,
                    'price'             => $price,
                    'original_price'    => (float) $product->price,
                    'image'             => ($variant && $variant->image) ? $variant->image : $product->image,
                    'quantity'          => $item->quantity,
                ]];
            })
            ->toArray();
    }

    /**
     * Lấy giỏ hàng đã filter theo danh sách item đang checkout (partial checkout).
     * Dùng chung cho index, applyVoucher, removeVoucher để đảm bảo
     * combo chỉ tính trên sản phẩm đang thanh toán.
     */
    private function getCheckoutCart(): array
    {
        $cart = $this->getCart();
        $selectedIds = session('checkout_item_ids', []);

        if (!empty($selectedIds)) {
            $selectedIds = array_map('intval', $selectedIds);
            $cart = array_filter($cart, fn($item, $key) => in_array((int) $key, $selectedIds), ARRAY_FILTER_USE_BOTH);
        }

        return $cart;
    }

    /**
     * Nhận danh sách cart_item IDs đã chọn từ trang giỏ hàng,
     * lưu vào session rồi chuyển hướng sang trang thanh toán.
     */
    public function selectItems(Request $request)
    {
        $request->validate([
            'item_ids'   => 'required|array|min:1',
            'item_ids.*' => 'integer',
        ]);

        session(['checkout_item_ids' => $request->input('item_ids')]);

        return redirect()->route('checkout');
    }

    public function index()
    {
        $selectedIds = session('checkout_item_ids', []);

        $cart = $this->getCheckoutCart();

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống!');
        }

        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $carriers = ShippingCarrier::where('is_active', true)->get();
        $isPartialCheckout = !empty($selectedIds);

        // Lấy discount từ combo
        $comboDiscountResult = \App\Models\ProductCombo::calculateCartDiscount($cart);
        $comboDiscount = $comboDiscountResult['amount'];
        $comboDetails = $comboDiscountResult['details'];

        $initialVouchers = [];
        foreach ($comboDetails as $cd) {
            $initialVouchers[] = [
                'code' => $cd['name'],
                'discount' => $cd['discount_amount'],
                'is_combo' => true
            ];
        }

        $appliedVoucherCodes = session('applied_vouchers', []);
        if (!empty($appliedVoucherCodes)) {
            $vouchers = \App\Models\Voucher::whereIn('code', $appliedVoucherCodes)
                ->get()
                ->sortBy(fn($v) => array_search($v->code, $appliedVoucherCodes))
                ->values();

            $result = \App\Models\Voucher::calculateStackedDiscount($vouchers, $subtotal - $comboDiscount);
            $initialVouchers = array_merge($initialVouchers, $result['breakdown']);
        }

        return view('shop.checkout', compact('cart', 'subtotal', 'carriers', 'isPartialCheckout', 'comboDiscount', 'comboDetails', 'initialVouchers'));
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

        // Lấy discount từ combo
        $cart = $this->getCheckoutCart();
        $comboDiscountResult = \App\Models\ProductCombo::calculateCartDiscount($cart);
        $comboDiscount = $comboDiscountResult['amount'];

        // Tính lại discount cho TOÀN BỘ danh sách (mã cũ + mã mới) theo đúng
        // thứ tự áp dụng, để đảm bảo mã mới còn tác dụng khi stack với các mã trước.
        $newAppliedCodes = [...$appliedCodes, $code];
        $vouchers        = $this->loadVouchersInOrder($newAppliedCodes);
        $result          = Voucher::calculateStackedDiscount($vouchers, $request->amount - $comboDiscount);

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

        $breakdown = $result['breakdown'];
        foreach ($comboDiscountResult['details'] as $cd) {
            array_unshift($breakdown, [
                'code' => $cd['name'],
                'discount' => $cd['discount_amount']
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Áp dụng mã "' . $code . '" thành công! Giảm thêm ' . number_format($newVoucherEntry['discount'], 0, ',', '.') . 'đ',
            'discount' => $result['total'] + $comboDiscount,
            'vouchers' => $breakdown,
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

        $cart = $this->getCheckoutCart();
        $comboDiscountResult = \App\Models\ProductCombo::calculateCartDiscount($cart);
        $comboDiscount = $comboDiscountResult['amount'];

        $result = empty($appliedCodes)
            ? ['total' => 0, 'breakdown' => []]
            : Voucher::calculateStackedDiscount($this->loadVouchersInOrder($appliedCodes), $request->amount - $comboDiscount);

        $breakdown = $result['breakdown'];
        foreach ($comboDiscountResult['details'] as $cd) {
            array_unshift($breakdown, [
                'code' => $cd['name'],
                'discount' => $cd['discount_amount']
            ]);
        }

        return response()->json([
            'success'  => true,
            'discount' => $result['total'] + $comboDiscount,
            'vouchers' => $breakdown,
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
            'idempotency_key' => 'required|string',
        ]);

        $idempotencyKey = $request->input('idempotency_key');
        $cacheKey = 'checkout_idempotency_' . $idempotencyKey;

        // Lock xử lý trong 10 giây để tránh 2 request xử lý song song
        $lock = Cache::lock('lock_' . $cacheKey, 10);
        if (!$lock->get()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Đơn hàng đang được xử lý, vui lòng đợi...'], 429);
            }
            return redirect()->back()->with('error', 'Đơn hàng đang được xử lý, vui lòng đợi...');
        }

        try {
            if (Cache::has($cacheKey)) {
                $existingOrderId = Cache::get($cacheKey);
                $existingOrder = Order::find($existingOrderId);
                if ($existingOrder) {
                    $checkoutItemIds = session('checkout_item_ids', []);
                    if (!empty($checkoutItemIds)) {
                        CartItem::where('user_id', Auth::id())->whereIn('id', $checkoutItemIds)->delete();
                    } else {
                        CartItem::where('user_id', Auth::id())->delete();
                    }
                    session()->forget(['applied_vouchers', 'checkout_item_ids']);

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success'      => true,
                            'message'      => 'Đặt hàng thành công!',
                            'order_id'     => $existingOrder->id,
                            'redirect_url' => route('checkout.success', ['id' => $existingOrder->id]),
                        ]);
                    }
                    return redirect()->route('checkout.success', $existingOrder->id);
                }
            }

            $cart = $this->getCart();

            // Chỉ lấy các sản phẩm đã chọn từ trang giỏ hàng
            $selectedIds = session('checkout_item_ids', []);
            if (!empty($selectedIds)) {
                $selectedIds = array_map('intval', $selectedIds);
                $cart = array_filter($cart, fn($item, $key) => in_array((int) $key, $selectedIds), ARRAY_FILTER_USE_BOTH);
            }

            if (empty($cart)) {
                return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống!');
            }
            $order = DB::transaction(function () use ($request, $cart) {

                // 1. Kiểm tra tồn kho — theo biến thể nếu khách chọn màu/size,
                // ngược lại theo tồn kho chung của sản phẩm.
                // Giữ lại $product/$variant đã lock để tái sử dụng ở bước 6,
                // tránh việc phải SELECT lại cùng bản ghi lần thứ hai.
                $lockedProducts = [];
                $lockedVariants = [];

                foreach ($cart as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['id']);
                    $label   = $product->name . (!empty($item['variant_name']) ? " ({$item['variant_name']})" : '');
                    $lockedProducts[$item['id']] = $product;

                    if (!empty($item['variant_id'])) {
                        $variant = \App\Models\ProductVariant::lockForUpdate()->find($item['variant_id']);
                        if (!$variant || $variant->stock < $item['quantity']) {
                            $available = $variant->stock ?? 0;
                            throw new \Exception("Sản phẩm '{$label}' chỉ còn {$available} trong kho.");
                        }
                        $lockedVariants[$item['variant_id']] = $variant;
                    } elseif ($product->stock < $item['quantity']) {
                        throw new \Exception("Sản phẩm '{$label}' chỉ còn {$product->stock} trong kho.");
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

                // 4. Lấy discount từ combo
                $comboDiscountResult = \App\Models\ProductCombo::calculateCartDiscount($cart);
                $discountAmount  = $comboDiscountResult['amount'];
                $appliedVouchers = []; 
                
                // Lưu combo details
                foreach ($comboDiscountResult['details'] as $cd) {
                    $appliedVouchers[] = [
                        'voucher' => new \App\Models\Voucher([
                            'code' => $cd['name'],
                            'id' => 0 // id=0 to avoid inserting to voucher_usage
                        ]),
                        'discount' => $cd['discount_amount']
                    ];
                }

                // 4.1 Áp dụng voucher (hỗ trợ nhiều mã cùng lúc, tính tuần tự)
                $voucherCodes    = $request->input('voucher_codes') ?: session('applied_vouchers', []);
                $voucherCodes    = array_values(array_unique(array_map('strtoupper', $voucherCodes)));

                if (!empty($voucherCodes)) {
                    $vouchers = Voucher::whereIn('code', $voucherCodes)
                        ->lockForUpdate()
                        ->get()
                        ->sortBy(fn($v) => array_search($v->code, $voucherCodes))
                        ->values();

                    $remaining = $subtotal - $discountAmount;

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

                // 6. Tạo OrderItems + Trừ kho (đúng biến thể nếu có chọn màu/size)
                foreach ($cart as $item) {
                    $discountPercent = $item['original_price'] > 0
                        ? round((1 - $item['price'] / $item['original_price']) * 100, 2)
                        : 0;

                    OrderItem::create([
                        'order_id'            => $order->id,
                        'product_id'          => $item['id'],
                        'product_variant_id'  => $item['variant_id'] ?? null,
                        'product_name'        => $item['name'],
                        'variant_name'        => $item['variant_name'] ?? null,
                        'quantity'            => $item['quantity'],
                        'price'               => $item['price'],
                        'original_price'      => $item['original_price'],
                        'discount_percent'    => $discountPercent > 0 ? $discountPercent : null,
                    ]);

                    $lockedProducts[$item['id']]->decreaseStock(
                        $item['quantity'],
                        $order->id,
                        $item['variant_id'] ?? null,
                        $item['variant_id'] ? ($lockedVariants[$item['variant_id']] ?? null) : null
                    );
                }

                // 7. Lưu voucher usage (từng mã trong danh sách đã áp dụng)
                foreach ($appliedVouchers as $entry) {
                    if ($entry['voucher']->id == 0) continue; // Bỏ qua nếu là mã sinh ra từ Combo
                    
                    $order->vouchers()->attach($entry['voucher']->id, ['discount_amount' => $entry['discount']]);

                    if (Auth::check()) {
                        $usage = VoucherUsage::firstOrNew([
                            'voucher_id' => $entry['voucher']->id,
                            'user_id'    => Auth::id(),
                        ]);
                        $usage->order_id = $order->id;
                        $usage->used_count = $usage->exists ? $usage->used_count + 1 : 1;
                        $usage->save();
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

                // 10. Thông báo in-app & Email xác nhận đơn hàng
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
                
                try {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderConfirmation($order));
                } catch (\Exception $e) {
                    Log::error('Lỗi gửi email xác nhận đặt hàng: ' . $e->getMessage());
                }

                return $order;
            });

            // Chỉ xóa các sản phẩm đã thanh toán, giữ lại phần còn lại trong giỏ
            $checkoutItemIds = session('checkout_item_ids', []);
            if (!empty($checkoutItemIds)) {
                CartItem::where('user_id', Auth::id())
                    ->whereIn('id', $checkoutItemIds)
                    ->delete();
            } else {
                CartItem::where('user_id', Auth::id())->delete();
            }
            session()->forget(['applied_vouchers', 'checkout_item_ids']);

            Cache::put($cacheKey, $order->id, now()->addHours(24));

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
        } finally {
            $lock?->release();
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