<!-- resources/views/shop/cart-summary.blade.php -->

<div class="p-4">
    <h1 class="display-6 mb-4">Tổng <span class="fw-normal">Giỏ Hàng</span></h1>
    
    <div class="d-flex justify-content-between mb-3">
        <h5 class="mb-0">Tạm tính:</h5>
        <p class="mb-0">{{ number_format($total ?? 0, 0, ',', '.') }}đ</p>
    </div>

    @if(session('applied_voucher'))
    <div class="d-flex justify-content-between mb-3 text-success">
        <h5 class="mb-0">Giảm giá ({{ session('applied_voucher') }}):</h5>
        <p class="mb-0">- {{ number_format($discount ?? 0, 0, ',', '.') }}đ</p>
    </div>
    @endif

    <div class="d-flex justify-content-between border-top pt-3">
        <h5 class="mb-0 fw-bold">Tổng cộng</h5>
        <p class="mb-0 fw-bold text-primary fs-5">
            {{ number_format(($total ?? 0) - ($discount ?? 0), 0, ',', '.') }}đ
        </p>
    </div>
</div>

@if(session('cart') && count(session('cart')) > 0)
    <a href="{{ route('checkout') }}" class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase w-100">
        Tiến hành thanh toán
    </a>
@else
    <button disabled class="btn border-secondary rounded-pill px-4 py-3 text-muted text-uppercase w-100">
        Chưa có sản phẩm
    </button>
@endif