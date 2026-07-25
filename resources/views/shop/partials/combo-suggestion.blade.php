@if(!empty($combos) && count($combos))
<div class="col-lg-12 mt-4">
    <h4 class="mb-3"><i class="fa fa-tags text-primary me-2"></i>Mua kèm giảm giá</h4>
    <div class="row g-4">
        @foreach($combos as $combo)
            @php
                $partner = $combo['partner'];
                $discountPercent = $combo['discount_percent'];
                $comboTotal = $product->effective_price + $partner->effective_price;
                $comboDiscounted = $comboTotal * (1 - $discountPercent / 100);
            @endphp
            <div class="col-md-6">
                <div class="border rounded p-3 d-flex align-items-center combo-suggestion"
                     data-product-id="{{ $product->id }}"
                     data-partner-id="{{ $partner->id }}">
                    <img src="{{ img_url($partner->image, asset('img/product-4.png')) }}"
                         class="rounded me-3" style="width: 70px; height: 70px; object-fit: cover;" alt="{{ $partner->name }}">
                    <div class="flex-grow-1">
                        <p class="mb-1 small text-muted">Thường được mua cùng</p>
                        <p class="mb-1 fw-bold">{{ $partner->name }}</p>
                        <p class="mb-2">
                            <span class="text-decoration-line-through text-muted small">{{ number_format($comboTotal, 0, ',', '.') }}đ</span>
                            <span class="text-primary fw-bold ms-2">{{ number_format($comboDiscounted, 0, ',', '.') }}đ</span>
                            <span class="badge bg-danger ms-1">-{{ $discountPercent }}%</span>
                        </p>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 add-combo-to-cart">
                            <i class="fa fa-shopping-bag me-1"></i> Thêm cả 2 vào giỏ
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    if (!e.target.closest('.add-combo-to-cart')) return;

    var btn = e.target.closest('.add-combo-to-cart');
    var wrap = btn.closest('.combo-suggestion');
    var productId = wrap.dataset.productId;
    var partnerId = wrap.dataset.partnerId;
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang thêm...';

    function addOne(id) {
        return fetch("{{ route('cart.add') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ product_id: id })
        }).then(r => r.json());
    }

    Promise.all([addOne(productId), addOne(partnerId)])
        .then(function (results) {
            var ok = results.every(r => r.success);
            if (typeof showCartToast === 'function') {
                showCartToast(ok ? 'Đã thêm combo vào giỏ hàng!' : 'Có lỗi khi thêm combo!', ok);
            }
            var last = results[results.length - 1];
            if (ok && typeof updateCartCount === 'function' && last.cart_count) {
                updateCartCount(last.cart_count);
            }
        })
        .catch(function () {
            if (typeof showCartToast === 'function') showCartToast('Lỗi kết nối. Vui lòng thử lại!', false);
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
});
</script>
@endpush
@endif
