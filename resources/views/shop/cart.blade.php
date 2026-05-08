@extends('layouts.app')

@section('title', 'Giỏ hàng - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Giỏ hàng của bạn</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="#">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-white">Giỏ hàng</li>
        </ol>
    </div>
    
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Sản phẩm</th>
                            <th scope="col">Mã SP</th>
                            <th scope="col">Đơn giá</th>
                            <th scope="col">Số lượng</th>
                            <th scope="col">Thành tiền</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
    @php $total = 0 @endphp
    @forelse($cart as $item)
        @php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; @endphp
        <tr>
            <th scope="row">
                <p class="mb-0 py-4">{{ $item['name'] }}</p>
            </th>
            <td>
                <p class="mb-0 py-4 text-primary">
                    {{ number_format($item['price'], 0, ',', '.') }}đ
                </p>
            </td>
            <td>
                <form action="{{ route('cart.update', $item['id']) }}" method="POST"
                      class="input-group quantity py-4" style="width: 130px;">
                    @csrf
                    <div class="input-group-btn">
                        <button type="submit" name="quantity"
                                value="{{ $item['quantity'] - 1 }}"
                                class="btn btn-sm btn-minus rounded-circle bg-light border">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                    <input type="text" class="form-control form-control-sm text-center border-0"
                           value="{{ $item['quantity'] }}" readonly>
                    <div class="input-group-btn">
                        <button type="submit" name="quantity"
                                value="{{ $item['quantity'] + 1 }}"
                                class="btn btn-sm btn-plus rounded-circle bg-light border">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </form>
            </td>
            <td>
                <p class="mb-0 py-4 text-primary fw-bold">
                    {{ number_format($subtotal, 0, ',', '.') }}đ
                </p>
            </td>
            <td class="py-4">
                <form action="{{ route('cart.remove', $item['id']) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-md rounded-circle bg-light border"
                            title="Xóa sản phẩm">
                        <i class="fa fa-times text-danger"></i>
                    </button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center py-5">
                Giỏ hàng đang trống.
                <a href="{{ route('shop.index') }}">Tiếp tục mua sắm</a>
            </td>
        </tr>
    @endforelse
</tbody>
                </table>
            </div>
            
            <div class="mt-5">
                <input type="text" class="border-0 border-bottom rounded me-5 py-3 mb-4" placeholder="Mã giảm giá">
                <button class="btn btn-primary rounded-pill px-4 py-3 text-white" type="button">Áp dụng</button>
            </div>
            
            <div class="row g-4 justify-content-end">
                <div class="col-8"></div>
                <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                    <div class="bg-light rounded">
                        <div class="p-4">
                            <h1 class="display-6 mb-4">Tổng <span class="fw-normal">đơn hàng</span></h1>
                            <div class="d-flex justify-content-between mb-4">
                                <h5 class="mb-0 me-4">Tạm tính:</h5>
                                <p class="mb-0">3.390.000đ</p>
                            </div>
                            <div class="d-flex justify-content-between">
                                <h5 class="mb-0 me-4">Vận chuyển:</h5>
                                <div>
                                    <p class="mb-0">Đồng giá: 30.000đ</p>
                                </div>
                            </div>
                            <p class="mb-0 text-end mt-2">Giao hàng toàn quốc.</p>
                        </div>
                        <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                            <h5 class="mb-0 ps-4 me-4">Thành tiền</h5>
                            <p class="mb-0 pe-4 fw-bold text-primary fs-5">3.420.000đ</p>
                        </div>
                        <div class="d-flex justify-content-center mb-4">
                            <a href="#" class="btn btn-primary rounded-pill px-4 py-3 text-uppercase text-white w-75">
                                Tiến hành thanh toán
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection