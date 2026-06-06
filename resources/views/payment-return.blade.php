@extends('layouts.app')

@section('title', 'Kết quả thanh toán - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Kết quả thanh toán</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Kết quả thanh toán</li>
    </ol>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            @if($success ?? false)
            {{-- ✅ SUCCESS --}}
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <div class="success-icon mb-4">
                    <svg width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="48" fill="none" stroke="#22c55e" stroke-width="4"/>
                        <path d="M25 50 L43 68 L75 32" fill="none" stroke="#22c55e" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"
                              style="stroke-dasharray:80; stroke-dashoffset:80; animation: drawCheck 0.6s ease 0.3s forwards;"/>
                    </svg>
                </div>
                <h2 class="text-success fw-bold mb-2">Thanh toán thành công!</h2>
                <p class="text-muted mb-4">Đơn hàng của bạn đã được xác nhận thanh toán.</p>

                @if(isset($order))
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-start">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Mã đơn hàng</span>
                        <strong class="font-monospace text-primary">{{ $order->tracking_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Số tiền</span>
                        <strong class="text-success fs-5">{{ number_format($order->grand_total ?? 0, 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Phương thức</span>
                        <span class="badge bg-primary">{{ strtoupper($order->payment_method) }}</span>
                    </div>
                </div>
                @endif

                <div class="d-flex gap-3 justify-content-center">
                    @if(isset($order))
                    <a href="{{ route('checkout.success', $order->id) }}" class="btn btn-primary rounded-pill px-4 py-2">
                        Xem đơn hàng
                    </a>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                        Về trang chủ
                    </a>
                </div>
            </div>

            @else
            {{-- ❌ FAILED / CANCELLED --}}
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <div class="fail-icon mb-4">
                    <svg width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="48" fill="none" stroke="#ef4444" stroke-width="4"/>
                        <path d="M33 33 L67 67 M67 33 L33 67" fill="none" stroke="#ef4444" stroke-width="6" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="text-danger fw-bold mb-2">Thanh toán thất bại</h2>
                <p class="text-muted mb-2">{{ $message ?? 'Giao dịch không thể hoàn tất.' }}</p>

                @if(isset($errorCode))
                <p class="text-muted small mb-4">Mã lỗi: <code>{{ $errorCode }}</code></p>
                @endif

                <div class="alert alert-light border rounded-3 text-start mb-4">
                    <p class="mb-1 fw-semibold">Một số lý do có thể xảy ra:</p>
                    <ul class="mb-0 small text-muted">
                        <li>Số dư tài khoản không đủ</li>
                        <li>Giao dịch bị từ chối bởi ngân hàng</li>
                        <li>Phiên thanh toán hết hạn</li>
                        <li>Bạn đã hủy giao dịch</li>
                    </ul>
                </div>

                <div class="d-flex gap-3 justify-content-center">
                    @if(isset($order))
                    <a href="{{ route('checkout') }}" class="btn btn-primary rounded-pill px-4 py-2">
                        Thử lại
                    </a>
                    <a href="{{ route('checkout.success', $order->id) }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                        Xem đơn hàng
                    </a>
                    @else
                    <a href="{{ route('cart.index') }}" class="btn btn-primary rounded-pill px-4 py-2">
                        Quay lại giỏ hàng
                    </a>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<style>
@keyframes drawCheck {
    to { stroke-dashoffset: 0; }
}
.success-icon, .fail-icon { display: flex; justify-content: center; }
</style>
@endsection