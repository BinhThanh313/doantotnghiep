@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Chi tiết đơn hàng</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">#{{ $order->tracking_number }}</li>
    </ol>
</div>

<div class="container py-5">
    <div class="row g-4">

        {{-- LEFT: Products + Totals --}}
        <div class="col-lg-8">

            {{-- Order Items --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 wow fadeInLeft" data-wow-delay="0.1s">
                <div class="card-header bg-white border-0 pt-4 pb-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Sản phẩm đã đặt</h5>
                        <span class="badge rounded-pill
                            @if($order->status === 'completed') bg-success
                            @elseif($order->status === 'cancelled') bg-danger
                            @elseif($order->status === 'shipped') bg-info
                            @else bg-warning text-dark @endif">
                            {{ $order->status_label }}
                        </span>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    @foreach($order->items as $item)
                    <div class="d-flex align-items-center py-3 border-bottom last:border-0">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-light rounded-3" style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;">
                                @if($item->product?->image)
                                    <img src="{{ img_url($item->product->image) }}"
                                         class="img-fluid rounded-3" style="max-width:60px;max-height:60px;object-fit:cover;">
                                @else
                                    <i class="fas fa-box text-muted"></i>
                                @endif
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-semibold">{{ $item->product_name }}</p>
                            @if($item->variant_name)
                            <small class="text-muted">{{ $item->variant_name }}</small>
                            @endif
                        </div>
                        <div class="text-end">
                            <p class="mb-0 text-muted small">{{ number_format($item->price, 0, ',', '.') }}đ × {{ $item->quantity }}</p>
                            <p class="mb-0 fw-bold text-primary">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</p>
                        </div>
                    </div>
                    @endforeach

                    {{-- Totals --}}
                    <div class="pt-3">
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span>Tạm tính</span>
                            <span>{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span>Phí vận chuyển</span>
                            <span>{{ $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.') . 'đ' : 'Miễn phí' }}</span>
                        </div>
                        @if(($order->discount_amount ?? 0) > 0)
                        <div class="d-flex justify-content-between text-success small mb-2">
                            <span>Giảm giá</span>
                            <span>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-3 mt-2">
                            <span>Tổng cộng</span>
                            <span class="text-primary">
                                {{ number_format($order->total_amount + ($order->shipping_fee ?? 0) - ($order->discount_amount ?? 0), 0, ',', '.') }}đ
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shipment Info --}}
            @if($order->shipment)
            <div class="card border-0 shadow-sm rounded-4 mb-4 wow fadeInLeft" data-wow-delay="0.2s">
                <div class="card-body px-4 py-4">
                    <h5 class="fw-bold mb-4">🚚 Thông tin vận chuyển</h5>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Đơn vị vận chuyển</p>
                            <p class="fw-semibold mb-0">{{ $order->shipment->carrier?->name ?? 'Chưa cập nhật' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Mã vận đơn</p>
                            <p class="fw-semibold mb-0 font-monospace">{{ $order->shipment->tracking_number ?? '—' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Trạng thái</p>
                            <span class="badge rounded-pill
                                @if($order->shipment->status === 'delivered') bg-success
                                @elseif($order->shipment->status === 'in_transit') bg-info
                                @elseif($order->shipment->status === 'failed') bg-danger
                                @else bg-warning text-dark @endif">
                                {{ $order->shipment->status_label }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Dự kiến giao</p>
                            <p class="fw-semibold mb-0">
                                {{ $order->shipment->estimated_delivery?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT: Payment + Address --}}
        <div class="col-lg-4">

            {{-- Payment Info --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 wow fadeInRight" data-wow-delay="0.1s">
                <div class="card-body px-4 py-4">
                    <h5 class="fw-bold mb-4">💳 Thanh toán</h5>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted small">Phương thức</span>
                        <span class="badge bg-primary">{{ strtoupper($order->payment_method) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted small">Trạng thái</span>
                        <span class="badge rounded-pill
                            @if($order->payment_status === 'paid') bg-success
                            @elseif($order->payment_status === 'refunded') bg-secondary
                            @else bg-warning text-dark @endif">
                            {{ match($order->payment_status) {
                                'paid' => '✓ Đã thanh toán',
                                'refunded' => 'Đã hoàn tiền',
                                default => 'Chưa thanh toán'
                            } }}
                        </span>
                    </div>

                    @if($order->payment)
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted small">Mã giao dịch</span>
                        <code class="small text-secondary">{{ $order->payment->transaction_id ?? '—' }}</code>
                    </div>
                    @if($order->payment->paid_at)
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Thời gian thanh toán</span>
                        <span class="small">{{ $order->payment->paid_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    @endif

                    {{-- Bank transfer QR (if pending) --}}
                    @if(strtolower($order->payment_method) === 'bank' && $order->payment_status !== 'paid' && isset($bankInfo))
                    <hr>
                    <p class="text-muted small fw-semibold mb-2">Thông tin chuyển khoản</p>
                    <div class="bg-light rounded-3 p-3 mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Ngân hàng</small>
                            <small class="fw-semibold">{{ $bankInfo['bank_name'] }}</small>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Số TK</small>
                            <small class="fw-bold font-monospace">{{ $bankInfo['account_number'] }}</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Chủ TK</small>
                            <small class="fw-semibold">{{ $bankInfo['account_name'] }}</small>
                        </div>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small mb-2">
                        <strong>Nội dung:</strong> {{ $bankInfo['transfer_note'] }}
                    </div>
                    @if(!empty($bankInfo['qr_url']))
                    <div class="text-center">
                        <img src="{{ $bankInfo['qr_url'] }}" alt="QR" class="img-fluid rounded-3" style="max-width:160px;">
                        <p class="text-muted small mt-1">Quét QR để chuyển khoản</p>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            {{-- Delivery Address --}}
            <div class="card border-0 shadow-sm rounded-4 wow fadeInRight" data-wow-delay="0.2s">
                <div class="card-body px-4 py-4">
                    <h5 class="fw-bold mb-4">📍 Địa chỉ giao hàng</h5>
                    <p class="fw-semibold mb-1">{{ $order->customer_name }}</p>
                    <p class="text-muted small mb-1"><i class="fas fa-phone me-2"></i>{{ $order->customer_phone }}</p>
                    <p class="text-muted small mb-1"><i class="fas fa-envelope me-2"></i>{{ $order->customer_email }}</p>
                    <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt me-2"></i>{{ $order->address }}, {{ $order->province }}</p>
                </div>
            </div>

        </div>
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('home') }}" class="btn btn-outline-primary rounded-pill px-4 py-2">
            <i class="fas fa-home me-2"></i>Về trang chủ
        </a>
    </div>
</div>
@endsection