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
    @if(session('cart') && count(session('cart')) > 0)
        @foreach($cart as $id => $item)
        <tr>
            <th scope="row">
                <div class="d-flex align-items-center">
                    {{-- Đảm bảo đường dẫn ảnh đúng với thư mục public của bạn --}}
                    <img src="{{ asset($item['image'] ?? 'img/default.jpg') }}" class="img-fluid me-5 rounded-circle" style="width: 80px; height: 80px;" alt="">
                </div>
            </th>
            <td>
                <p class="mb-0 mt-4">{{ $item['name'] }}</p>
            </td>
            <td>
                <p class="mb-0 mt-4">{{ number_format($item['price'], 0, ',', '.') }}đ</p>
            </td>
            <td>
    <div class="input-group quantity mt-4" style="width: 120px;">
        {{-- Form Cập nhật số lượng --}}
        <form action="{{ route('cart.update', $id) }}" method="POST" class="d-flex align-items-center w-100">
            @csrf
            
            <div class="input-group-btn">
                <button type="button" class="btn btn-sm btn-minus rounded-circle bg-light border" 
                    onclick="var input = this.form.querySelector('input[name=quantity]'); input.stepDown(); this.form.submit();">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
            
            <input type="number" name="quantity" class="form-control form-control-sm text-center border-0 mx-1" 
                value="{{ $item['quantity'] }}" min="1" 
                onchange="this.form.submit();">
            
            <div class="input-group-btn">
                <button type="button" class="btn btn-sm btn-plus rounded-circle bg-light border" 
                    onclick="var input = this.form.querySelector('input[name=quantity]'); input.stepUp(); this.form.submit();">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
            
        </form>
    </div>
</td>
            <td>
                <p class="mb-0 mt-4">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</p>
            </td>
            <td>
                {{-- Form Xóa sản phẩm --}}
                <form action="{{ route('cart.remove', $id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-md rounded-circle bg-light border mt-4">
                        <i class="fa fa-times text-danger"></i>
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
    @else
        <tr>
            <td colspan="6" class="text-center py-4 text-danger fw-bold">
                Giỏ hàng của bạn đang trống! <br>
                <a href="{{ route('shop.index') }}" class="btn btn-primary mt-2 text-white">Tiếp tục mua sắm</a>
            </td>
        </tr>
    @endif
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
                <h1 class="display-6 mb-4">Tổng <span class="fw-normal">Giỏ Hàng</span></h1>
                <div class="d-flex justify-content-between mb-4">
                    <h5 class="mb-0 me-4">Tạm tính:</h5>
                    <p class="mb-0">{{ number_format($total ?? 0, 0, ',', '.') }}đ</p>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-4">
                    <h5 class="mb-0 me-4">Phí ship</h5>
                    <div class="">
                        <p class="mb-0">Sẽ tính ở bước sau</p>
                    </div>
                </div>
            </div>
            <div class="py-4 mb-4 border-top border-bottom d-flex justify-content-between">
                <h5 class="mb-0 ps-4 me-4">Tổng cộng</h5>
                <p class="mb-0 pe-4 text-primary fw-bold">{{ number_format($total ?? 0, 0, ',', '.') }}đ</p>
            </div>
            
            {{-- CHỈ HIỂN THỊ NÚT THANH TOÁN KHI CÓ ĐỒ TRONG GIỎ --}}
            @if(session('cart') && count(session('cart')) > 0)
                <a href="{{ route('checkout') }}" class="btn border-secondary rounded-pill px-4 py-3 text-primary text-uppercase mb-4 w-100">
                    Tiến hành thanh toán
                </a>
            @else
                <button disabled class="btn border-secondary rounded-pill px-4 py-3 text-muted text-uppercase mb-4 ms-4 w-100">
                    Chưa có sản phẩm
                </button>
            @endif
        </div>
    </div>
</div>
        </div>
    </div>
@endsection