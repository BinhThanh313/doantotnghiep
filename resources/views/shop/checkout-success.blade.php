@extends('layouts.app')
@section('title', 'Đặt hàng thành công - Electro')
@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Đặt hàng thành công!</h1>
    </div>

    <div class="container py-5 text-center">
        <i class="fas fa-check-circle display-1 text-success mb-4"></i>
        <h2>Cảm ơn bạn đã đặt hàng!</h2>
        <p class="text-muted">Mã đơn hàng: <strong>#{{ $order->id }}</strong></p>
        <p>Chúng tôi sẽ liên hệ với bạn qua số
           <strong>{{ $order->customer_phone }}</strong> để xác nhận.</p>

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
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 0, ',', '.') }}đ</td>
                        <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Tạm tính:</td>
                        <td class="text-primary fw-bold fs-5">
                            {{ number_format($order->total_amount, 0, ',', '.') }}đ
                        </td>
                    </tr>
                    @if($order->discount_amount > 0)
                    <tr>
                        <td colspan="3" class="text-end fw-bold text-success">Giảm giá:</td>
                        <td class="text-success fw-bold fs-5">
                            - {{ number_format($order->discount_amount, 0, ',', '.') }}đ
                        </td>
                    </tr>
                    @endif
                    @if($order->shipping_fee > 0)
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Phí vận chuyển:</td>
                        <td class="fw-bold fs-5">
                            {{ number_format($order->shipping_fee, 0, ',', '.') }}đ
                        </td>
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