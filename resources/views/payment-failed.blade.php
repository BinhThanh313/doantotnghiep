@extends('layouts.app')

@section('title', 'Thanh toán thất bại - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Thanh toán thất bại</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Thất bại</li>
    </ol>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">

                <div class="position-relative d-inline-block mb-4">
                    <svg width="110" height="110" viewBox="0 0 110 110" fill="none">
                        <circle cx="55" cy="55" r="52" stroke="#fee2e2" stroke-width="6" fill="#fef2f2"/>
                        <circle cx="55" cy="55" r="38" fill="#fee2e2"/>
                        <path d="M38 38 L72 72 M72 38 L38 72" stroke="#ef4444" stroke-width="7" stroke-linecap="round"/>
                    </svg>
                </div>

                <h2 class="fw-bold text-danger mb-2">Ôi không! Thanh toán thất bại</h2>
                <p class="text-muted mb-1">{{ $message ?? 'Giao dịch của bạn không thể hoàn tất.' }}</p>

                @if(isset($errorCode))
                <p class="text-muted small mb-4">Mã lỗi: <code class="bg-light px-2 py-1 rounded">{{ $errorCode }}</code></p>
                @endif

                @if(isset($order))
                <div class="card border-0 bg-light rounded-4 p-4 mb-4 text-start">
                    <p class="text-muted small mb-2 fw-semibold text-uppercase tracking-wide">Chi tiết đơn hàng</p>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Mã đơn</span>
                        <code class="text-primary">{{ $order->tracking_number }}</code>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Tổng tiền</span>
                        <strong>{{ number_format(($order->total_amount ?? 0) + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0), 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Trạng thái</span>
                        <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                    </div>
                </div>
                @endif

                <div class="card border-warning border-opacity-50 rounded-4 p-4 mb-4 text-start bg-warning bg-opacity-10">
                    <p class="fw-semibold mb-2 small">Gợi ý xử lý:</p>
                    <ul class="mb-0 small text-muted ps-3">
                        <li class="mb-1">Kiểm tra số dư tài khoản / ví điện tử</li>
                        <li class="mb-1">Thử phương thức thanh toán khác (COD / Chuyển khoản)</li>
                        <li class="mb-1">Liên hệ hotline <strong class="text-dark">0123 456 789</strong> để được hỗ trợ</li>
                        <li>Đơn hàng vẫn còn hiệu lực — thử lại để hoàn tất thanh toán</li>
                    </ul>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    @if(isset($order))
                    <a href="{{ url('/checkout') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="fas fa-redo me-2"></i>Thử lại
                    </a>
                    <a href="{{ route('checkout.success', $order->id) }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                        Xem đơn hàng
                    </a>
                    @else
                    <a href="{{ route('cart.index') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="fas fa-shopping-cart me-2"></i>Giỏ hàng
                    </a>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-light rounded-pill px-4 py-2">
                        Trang chủ
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection