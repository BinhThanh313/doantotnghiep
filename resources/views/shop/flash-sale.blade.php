@extends('layouts.app')
@section('title', 'Flash Sale - Electro')

@section('content')
<div class="container-fluid page-header py-5"
     style="background: linear-gradient(135deg, #ff4444 0%, #ff8c00 100%);">
    <h1 class="text-center text-white display-6 wow fadeInUp">⚡ Flash Sale</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Flash Sale</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">

        @if($flashSale)
        {{-- Countdown Banner --}}
        <div class="text-center mb-5 wow fadeInUp">
            <h2 class="fw-bold mb-2">{{ $flashSale->name }}</h2>
            @if($flashSale->description)
            <p class="text-muted">{{ $flashSale->description }}</p>
            @endif
            <div class="d-flex justify-content-center align-items-center gap-3 mt-3">
                <span class="text-muted">Kết thúc sau:</span>
                <div id="flash-countdown" class="d-flex gap-2"
                     data-ends="{{ $flashSale->ends_at->toISOString() }}">
                    @foreach(['hours' => 'GIỜ', 'mins' => 'PHÚT', 'secs' => 'GIÂY'] as $unit => $label)
                    <div class="text-center">
                        <div class="bg-danger text-white fw-bold rounded px-3 py-2 fs-4 countdown-{{ $unit }}">00</div>
                        <small class="text-muted">{{ $label }}</small>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Products Grid --}}
        @if($flashSale->activeItems->isNotEmpty())
        <div class="row g-4">
            @foreach($flashSale->activeItems as $item)
            @php $product = $item->product; @endphp
            @if($product)
            <div class="col-md-6 col-lg-4 col-xl-3 wow fadeInUp">
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                    {{-- Discount Badge --}}
                    @php $discPct = $product->price > $item->sale_price
                        ? round((1 - $item->sale_price / $product->price) * 100) : 0; @endphp
                    @if($discPct > 0)
                    <div class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 fw-bold"
                         style="font-size:13px; z-index:1; border-radius:0 0 8px 0;">
                        -{{ $discPct }}%
                    </div>
                    @endif

                    <a href="{{ route('shop.show', $product->id) }}">
                        <img src="{{ img_url($product->image, asset('img/product-3.png')) }}"
                             class="card-img-top" style="height:220px;object-fit:cover;"
                             alt="{{ $product->name }}">
                    </a>

                    <div class="card-body">
                        <a href="{{ route('shop.show', $product->id) }}"
                           class="d-block fw-semibold text-dark mb-2">{{ $product->name }}</a>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-danger fw-bold fs-5">
                                {{ number_format($item->sale_price, 0, ',', '.') }}đ
                            </span>
                            <del class="text-muted small">
                                {{ number_format($product->price, 0, ',', '.') }}đ
                            </del>
                        </div>

                        @if($item->qty_limit)
                        @php $pct = min(100, round(($item->qty_sold / $item->qty_limit) * 100)); @endphp
                        <div class="mb-1">
                            <div class="progress" style="height:8px; border-radius:4px;">
                                <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                            </div>
                            <small class="text-muted">
                                Đã bán {{ $item->qty_sold }}/{{ $item->qty_limit }}
                                @if($pct >= 80)<span class="text-danger fw-bold"> · Sắp hết!</span>@endif
                            </small>
                        </div>
                        @else
                        <small class="text-success">Không giới hạn số lượng</small>
                        @endif
                    </div>

                    <div class="card-footer bg-white border-0 pb-3 px-3">
                        @if($item->isSoldOut())
                        <button class="btn btn-secondary w-100 rounded-pill" disabled>Hết hàng</button>
                        @else
                        <button type="button"
                                class="btn btn-danger w-100 rounded-pill fw-bold flash-add-cart"
                                data-id="{{ $product->id }}">
                            <i class="fas fa-bolt me-2"></i>Mua ngay
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
        @else
        <div class="text-center py-5">
            <p class="text-muted fs-5">Chưa có sản phẩm nào trong Flash Sale này.</p>
        </div>
        @endif

        @else
        {{-- Không có Flash Sale đang chạy --}}
        <div class="text-center py-5 wow fadeInUp">
            <div class="display-1 mb-3">⚡</div>
            <h3 class="fw-bold mb-2">Hiện tại chưa có Flash Sale</h3>
            <p class="text-muted mb-4">Hãy quay lại sau để không bỏ lỡ ưu đãi cực sốc!</p>

            @if($upcomingSales->isNotEmpty())
            <h5 class="mb-3">Flash Sale sắp diễn ra:</h5>
            <div class="row justify-content-center g-3">
                @foreach($upcomingSales as $sale)
                <div class="col-md-4">
                    <div class="card border-warning h-100">
                        <div class="card-body text-center">
                            <h6 class="fw-bold">{{ $sale->name }}</h6>
                            <p class="text-muted small mb-1">Bắt đầu lúc:</p>
                            <p class="fw-bold text-warning">
                                {{ $sale->starts_at->format('H:i - d/m/Y') }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <a href="{{ route('shop.index') }}" class="btn btn-primary rounded-pill px-5 py-3 mt-4">
                Tiếp tục mua sắm
            </a>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ── Countdown ──────────────────────────────────────
    const el = document.getElementById('flash-countdown');
    if (el) {
        const ends = new Date(el.dataset.ends).getTime();
        function tick() {
            const diff = Math.max(0, Math.floor((ends - Date.now()) / 1000));
            const pad = n => String(n).padStart(2, '0');
            el.querySelector('.countdown-hours').textContent = pad(Math.floor(diff / 3600));
            el.querySelector('.countdown-mins').textContent  = pad(Math.floor((diff % 3600) / 60));
            el.querySelector('.countdown-secs').textContent  = pad(diff % 60);
            if (diff > 0) setTimeout(tick, 1000);
            else location.reload();
        }
        tick();
    }

    // ── Add to cart AJAX ───────────────────────────────
    document.querySelectorAll('.flash-add-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const originalHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
                    this.innerHTML = '<i class="fas fa-check me-2"></i>Đã thêm!';
                    this.classList.replace('btn-danger', 'btn-success');
                    if (typeof showCartToast === 'function') showCartToast(data.message || 'Đã thêm vào giỏ hàng!', true);
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.replace('btn-success', 'btn-danger');
                        this.disabled = false;
                    }, 2000);
                } else {
                    if (typeof showCartToast === 'function') showCartToast(data.message || 'Có lỗi xảy ra!', false);
                    else alert(data.message || 'Có lỗi xảy ra!');
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                }
            })
            .catch(() => {
                if (typeof showCartToast === 'function') showCartToast('Lỗi kết nối!', false);
                else alert('Lỗi kết nối!');
                this.innerHTML = originalHTML;
                this.disabled = false;
            });
        });
    });
})();
</script>
@endpush