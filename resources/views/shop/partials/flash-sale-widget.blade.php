{{-- resources/views/shop/partials/flash-sale-widget.blade.php --}}
{{-- Gọi: @include('shop.partials.flash-sale-widget') --}}

@php
    $flashSale = \App\Models\FlashSale::running()
        ->with(['items' => fn($q) => $q->where('is_active', true)->with('product:id,name,image,price,stock')])
        ->latest('starts_at')
        ->first();
@endphp

@if($flashSale && $flashSale->items->isNotEmpty())
<div class="container-fluid flash-sale-section py-4"
     style="background: linear-gradient(135deg, #ff4444 0%, #ff8c00 100%);">
    <div class="container">
        <!-- Header với đồng hồ đếm ngược -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <h3 class="text-white fw-bold mb-0">⚡ FLASH SALE</h3>
                <span class="badge bg-white text-danger fw-bold fs-6">
                    {{ $flashSale->name }}
                </span>
                
                {{-- Thêm vào cuối phần header của widget --}}
                <a href="{{ route('flash-sale') }}" class="btn btn-outline-light btn-sm rounded-pill">
                    Xem tất cả →
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 text-white">
                <span class="small opacity-75">Kết thúc sau:</span>
                <div id="flash-sale-countdown" class="d-flex gap-1"
                     data-ends="{{ $flashSale->ends_at->toISOString() }}">
                    @foreach(['hours','mins','secs'] as $unit)
                    <div class="text-center">
                        <div class="bg-dark text-white fw-bold rounded px-2 py-1 fs-5 countdown-{{ $unit }}">00</div>
                        <div class="text-white opacity-75" style="font-size:10px;">
                            {{ ['hours'=>'GIỜ','mins'=>'PHÚT','secs'=>'GIÂY'][$unit] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- Products -->
        <div class="row g-3">
            @foreach($flashSale->items->take(6) as $item)
            @php $product = $item->product; @endphp
            @if($product)
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                    @php $discPct = $product->price > $item->sale_price
                        ? round((1 - $item->sale_price / $product->price) * 100) : 0; @endphp
                    @if($discPct > 0)
                    <div class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1"
                         style="font-size:11px; font-weight:700; z-index:1; border-radius:0 0 8px 0;">
                        -{{ $discPct }}%
                    </div>
                    @endif

                    <a href="{{ route('shop.show', $product->id) }}">
                        <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-3.png') }}"
                             class="card-img-top" style="height:140px;object-fit:cover;"
                             alt="{{ $product->name }}">
                    </a>

                    <div class="card-body p-2 text-center">
                        <p class="mb-1 fw-semibold" style="font-size:12px;line-height:1.3;
                           overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                            {{ $product->name }}
                        </p>
                        <p class="text-danger fw-bold mb-0" style="font-size:14px;">
                            {{ number_format($item->sale_price, 0, ',', '.') }}đ
                        </p>
                        <del class="text-muted" style="font-size:11px;">
                            {{ number_format($product->price, 0, ',', '.') }}đ
                        </del>

                        @if($item->qty_limit)
                        <div class="mt-1">
                            @php $pct = $item->qty_limit > 0
                                ? min(100, round(($item->qty_sold / $item->qty_limit) * 100)) : 0; @endphp
                            <div class="progress" style="height:6px; border-radius:3px;">
                                <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                            </div>
                            <small class="text-muted" style="font-size:10px;">
                                Đã bán {{ $item->qty_sold }}/{{ $item->qty_limit }}
                            </small>
                        </div>
                        @endif
                    </div>

                    {{-- Thay thế toàn bộ phần card-footer --}}
                    <div class="card-footer p-1 bg-white border-0">
                        @if($item->isSoldOut())
                        <button class="btn btn-danger btn-sm w-100 rounded-pill" disabled>Hết hàng</button>
                        @else
                        <button type="button"
                                class="btn btn-danger btn-sm w-100 rounded-pill flash-add-cart"
                                data-id="{{ $product->id }}">
                            Mua ngay
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
<script>
(function () {
    // Countdown
    const el = document.getElementById('flash-sale-countdown')
    if (!el) return
    const ends = new Date(el.dataset.ends).getTime()
    function tick () {
        const diff = Math.max(0, Math.floor((ends - Date.now()) / 1000))
        const h = Math.floor(diff / 3600)
        const m = Math.floor((diff % 3600) / 60)
        const s = diff % 60
        const pad = n => String(n).padStart(2, '0')
        el.querySelector('.countdown-hours').textContent = pad(h)
        el.querySelector('.countdown-mins').textContent  = pad(m)
        el.querySelector('.countdown-secs').textContent  = pad(s)
        if (diff > 0) setTimeout(tick, 1000)
        else el.closest('.flash-sale-section')?.remove()
    }
    tick()

    // Add to cart AJAX
    document.querySelectorAll('.flash-add-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id
            const originalText = this.innerHTML
            this.disabled = true
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'

            fetch('{{ route("cart.add") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '✓ Đã thêm'
                    this.classList.remove('btn-danger')
                    this.classList.add('btn-success')
                    setTimeout(() => {
                        this.innerHTML = originalText
                        this.classList.remove('btn-success')
                        this.classList.add('btn-danger')
                        this.disabled = false
                    }, 2000)
                } else {
                    alert(data.message || 'Có lỗi xảy ra')
                    this.innerHTML = originalText
                    this.disabled = false
                }
            })
            .catch(() => {
                alert('Lỗi kết nối!')
                this.innerHTML = originalText
                this.disabled = false
            })
        })
    })
})()
</script>
<script>
(function () {
    const el   = document.getElementById('flash-sale-countdown')
    if (!el) return
    const ends = new Date(el.dataset.ends).getTime()

    function tick () {
        const diff = Math.max(0, Math.floor((ends - Date.now()) / 1000))
        const h = Math.floor(diff / 3600)
        const m = Math.floor((diff % 3600) / 60)
        const s = diff % 60
        const pad = n => String(n).padStart(2, '0')
        el.querySelector('.countdown-hours').textContent = pad(h)
        el.querySelector('.countdown-mins').textContent  = pad(m)
        el.querySelector('.countdown-secs').textContent  = pad(s)
        if (diff > 0) setTimeout(tick, 1000)
        else el.closest('.flash-sale-section')?.remove()
    }
    tick()
})()
</script>
@endif
