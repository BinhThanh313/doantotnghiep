@extends('layouts.app')

@section('title', 'Trang cá nhân')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Trang cá nhân</h1>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Thông tin tài khoản</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Họ và tên:</div>
                        <div class="col-sm-9">{{ Auth::user()->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Email:</div>
                        <div class="col-sm-9">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 fw-bold">Ngày tham gia:</div>
                        <div class="col-sm-9">{{ Auth::user()->created_at->format('d/m/Y') }}</div>
                    </div>
                <div class="card shadow mt-4" id="order-history">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0 text-white">Lịch sử đơn hàng</h5>
    </div>
    <div class="card-body p-0">
        @if($orders->isEmpty())
            <p class="text-center py-4 text-muted">Bạn chưa có đơn hàng nào.</p>
        @else
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td><code>{{ $order->tracking_number }}</code></td>
                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="text-primary fw-bold">
                            {{ number_format($order->grand_total, 0, ',', '.') }}đ
                        </td>
                        <td>
                            <span class="badge rounded-pill
                                @if($order->status === 'completed') bg-success
                                @elseif($order->status === 'cancelled') bg-danger
                                @elseif(in_array($order->status, ['shipped','delivered'])) bg-info
                                @else bg-warning text-dark @endif">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('order.detail', $order->id) }}" 
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                Xem
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-4" id="pagination">
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('home') }}" class="btn btn-secondary">Về trang chủ</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">Đăng xuất</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection