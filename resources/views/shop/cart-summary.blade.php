<div class="p-4">
    <h1 class="display-6 mb-4">Tổng <span class="fw-normal">Giỏ Hàng</span></h1>
    
    <div class="d-flex justify-content-between mb-4">
        <h5 class="mb-0 me-4">Tạm tính:</h5>
        <p class="mb-0">{{ number_format($total ?? 0, 0, ',', '.') }}đ</p>
    </div>
    
    <div class="d-flex justify-content-between border-bottom pb-4">
        <h5 class="mb-0 me-4">Phí ship</h5>
        <p class="mb-0">Sẽ tính ở bước sau</p>
    </div>
</div>

<div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
    <h5 class="mb-0 ps-4 me-4">Tổng cộng</h5>
    <p class="mb-0 pe-4 text-primary fw-bold">{{ number_format($total ?? 0, 0, ',', '.') }}đ</p>
</div>

@if(session('cart') && count(session('cart')) > 0)
    <a href="{{ route('checkout') }}" class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 w-100">
        Tiến hành thanh toán
    </a>
@else
    <button disabled class="btn border-secondary rounded-pill px-4 py-3 text-muted text-uppercase mb-4 w-100">
        Chưa có sản phẩm
    </button>
@endif