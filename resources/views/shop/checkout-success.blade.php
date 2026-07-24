@extends('layouts.app')
@section('title', 'Đặt hàng thành công - Electro')
@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Đặt hàng thành công!</h1>
    </div>

    <div class="container py-5 text-center">
        <i class="fas fa-check-circle display-1 text-success mb-4"></i>
        <h2>Cảm ơn bạn đã đặt hàng!</h2>
        <p class="text-muted">Mã đơn hàng: <strong>{{ $order->tracking_number }}</strong></p>
        <p>Chúng tôi sẽ liên hệ với bạn qua số
           <strong>{{ $order->customer_phone }}</strong> để xác nhận.</p>

        {{-- ===== BANK TRANSFER INFO ===== --}}
        @if($bankInfo)
        <div class="row justify-content-center mt-4 mb-4">
            <div class="col-md-8 col-lg-6">
                <div class="card border-success shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0 text-white">🏦 Thông tin chuyển khoản</h5>
                    </div>
                    <div class="card-body text-start">
                        <div class="alert alert-warning py-2">
                            <strong>⚠️ Vui lòng chuyển khoản trong vòng 24 giờ</strong>
                        </div>
                        
                        <table class="table table-borderless table-sm mb-3">
                            <tr>
                                <td class="text-muted">Ngân hàng:</td>
                                <td><strong>{{ $bankInfo['bank_name'] }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số tài khoản:</td>
                                <td>
                                    <strong class="fs-5 text-primary">{{ $bankInfo['account_number'] }}</strong>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" 
                                            onclick="navigator.clipboard.writeText('{{ $bankInfo['account_number'] }}');this.textContent='✓ Đã copy'">
                                        Copy
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tên tài khoản:</td>
                                <td><strong>{{ $bankInfo['account_name'] }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số tiền:</td>
                                <td><strong class="text-success fs-5">{{ number_format($order->total_amount + $order->shipping_fee - $order->discount_amount, 0, ',', '.') }}đ</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nội dung CK:</td>
                                <td>
                                    <strong class="text-danger">{{ $bankInfo['transfer_content'] ?? 'ELECTRO ' . $order->tracking_number }}</strong>
                                    <button class="btn btn-sm btn-outline-secondary ms-2"
                                            onclick="navigator.clipboard.writeText('{{ $bankInfo['transfer_content'] ?? 'ELECTRO ' . $order->tracking_number }}');this.textContent='✓ Đã copy'">
                                        Copy
                                    </button>
                                </td>
                            </tr>
                        </table>

                        {{-- QR Code --}}
                        @if(!empty($bankInfo['qr_url']))
                        <div class="text-center mt-3">
                            <p class="text-muted small mb-2">Quét mã QR để chuyển khoản nhanh</p>
                            <img src="{{ $bankInfo['qr_url'] }}" 
                                 alt="QR chuyển khoản" 
                                 class="img-fluid rounded border"
                                 style="max-width: 200px;">
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ===== ORDER ITEMS TABLE ===== --}}
        <div class="table-responsive mt-4 mb-5">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if($item->variant_name)
                                <br><small class="text-muted">{{ $item->variant_name }}</small>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 0, ',', '.') }}đ</td>
                        <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Tạm tính:</td>
                        <td class="text-primary fw-bold fs-5">{{ number_format($order->total_amount, 0, ',', '.') }}đ</td>
                    </tr>
                    @if($order->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="text-end fw-bold text-success">Giảm giá:</td>
                        <td class="text-success fw-bold fs-5">- {{ number_format($order->discount_amount, 0, ',', '.') }}đ</td>
                    </tr>
                    @endif
                    @if($order->shipping_fee > 0)
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Phí vận chuyển:</td>
                        <td class="fw-bold fs-5">{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" class="text-end fw-bold">TỔNG CỘNG:</td>
                        <td class="text-primary fw-bold fs-5">
                            {{ number_format($order->total_amount + $order->shipping_fee - $order->discount_amount, 0, ',', '.') }}đ
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-5 py-3">
            Tiếp tục mua sắm
        </a>
    </div>
@endsection