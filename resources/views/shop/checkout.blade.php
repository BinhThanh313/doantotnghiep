@extends('layouts.app')

@section('title', 'Thanh toán - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Thanh toán</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="#">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-white">Thanh toán</li>
        </ol>
    </div>
    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-6 col-md-4 col-lg-2 border-start border-end wow fadeInUp" data-wow-delay="0.1s">
                <div class="p-4">
                    <div class="d-inline-flex align-items-center">
                        <i class="fa fa-sync-alt fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Đổi trả miễn phí</h6>
                            <p class="mb-0">Trong vòng 30 ngày!</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.2s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-telegram-plane fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Miễn phí ship</h6>
                            <p class="mb-0">Cho mọi đơn hàng</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.3s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-life-ring fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Hỗ trợ 24/7</h6>
                            <p class="mb-0">Sẵn sàng giải đáp</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.4s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-credit-card fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Quà tặng hấp dẫn</h6>
                            <p class="mb-0">Cho đơn trên 1.000.000đ</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.5s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lock fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Bảo mật thanh toán</h6>
                            <p class="mb-0">An toàn tuyệt đối</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.6s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-blog fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Bảo hành uy tín</h6>
                            <p class="mb-0">Chính hãng 100%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-light overflow-hidden py-5">
        <div class="container py-5">
            <h1 class="mb-4 wow fadeInUp" data-wow-delay="0.1s">Chi tiết thanh toán</h1>
            
            <form action="#" method="POST">
                @csrf
                <div class="row g-5">
                    <div class="col-md-12 col-lg-6 col-xl-6 wow fadeInUp" data-wow-delay="0.1s">
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Họ <sup>*</sup></label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Tên <sup>*</sup></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Số điện thoại <sup>*</sup></label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Địa chỉ Email <sup>*</sup></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Tỉnh/Thành phố <sup>*</sup></label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Địa chỉ cụ thể <sup>*</sup></label>
                            <input type="text" name="address" class="form-control" placeholder="Số nhà, tên đường, phường/xã..." required>
                        </div>
                        <hr>
                        <div class="form-item">
                            <label class="form-label my-3">Ghi chú đơn hàng (Tùy chọn)</label>
                            <textarea name="notes" class="form-control" spellcheck="false" cols="30" rows="6" placeholder="Ghi chú về thời gian giao hàng, địa điểm..."></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-12 col-lg-6 col-xl-6 wow fadeInUp" data-wow-delay="0.3s">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr class="text-center">
                                        <th scope="col" class="text-start">Tên sản phẩm</th>
                                        <th scope="col">Đơn giá</th>
                                        <th scope="col">SL</th>
                                        <th scope="col">Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- GHI CHÚ: Sau này lặp @foreach giỏ hàng ở đây --}}
                                    <tr class="text-center">
                                        <th scope="row" class="text-start py-4">Apple iPad Mini</th>
                                        <td class="py-4">2.690.000đ</td>
                                        <td class="py-4">2</td>
                                        <td class="py-4 fw-bold">5.380.000đ</td>
                                    </tr>
                                    <tr class="text-center">
                                        <th scope="row" class="text-start py-4">Camera thông minh</th>
                                        <td class="py-4">350.000đ</td>
                                        <td class="py-4">1</td>
                                        <td class="py-4 fw-bold">350.000đ</td>
                                    </tr>
                                    {{-- Kết thúc @foreach --}}
                                    
                                    <tr>
                                        <th scope="row"></th>
                                        <td class="py-4"></td>
                                        <td class="py-4"><p class="mb-0 text-dark py-2 text-end">Tạm tính</p></td>
                                        <td class="py-4">
                                            <div class="py-2 text-center border-bottom border-top">
                                                <p class="mb-0 text-dark">5.730.000đ</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"></th>
                                        <td class="py-4"><p class="mb-0 text-dark py-4 text-end">Phí ship</p></td>
                                        <td colspan="2" class="py-4">
                                            <div class="form-check text-start">
                                                <input type="radio" class="form-check-input bg-primary border-0" id="Shipping-1" name="shipping_fee" value="0" checked>
                                                <label class="form-check-label" for="Shipping-1">Miễn phí vận chuyển (Tiêu chuẩn)</label>
                                            </div>
                                            <div class="form-check text-start">
                                                <input type="radio" class="form-check-input bg-primary border-0" id="Shipping-2" name="shipping_fee" value="30000">
                                                <label class="form-check-label" for="Shipping-2">Giao hàng hỏa tốc: 30.000đ</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"></th>
                                        <td class="py-4"><p class="mb-0 text-dark text-uppercase py-2 text-end">Tổng cộng</p></td>
                                        <td class="py-4"></td>
                                        <td class="py-4">
                                            <div class="py-2 text-center border-bottom border-top">
                                                <p class="mb-0 text-primary fw-bold fs-5">5.730.000đ</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row g-0 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-2">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Payment-COD" name="payment_method" value="cod" checked>
                                    <label class="form-check-label fw-bold" for="Payment-COD">Thanh toán khi nhận hàng (COD)</label>
                                </div>
                                <p class="text-start text-dark small ms-4">Khách hàng thanh toán bằng tiền mặt khi shipper giao hàng tới.</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-2">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Payment-Bank" name="payment_method" value="bank">
                                    <label class="form-check-label fw-bold" for="Payment-Bank">Chuyển khoản ngân hàng</label>
                                </div>
                                <p class="text-start text-dark small ms-4">Chuyển khoản trực tiếp qua tài khoản ngân hàng của cửa hàng. Đơn hàng sẽ được xử lý sau khi nhận được tiền.</p>
                            </div>
                        </div>

                        <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                            <button type="submit" class="btn btn-primary border-secondary py-3 px-4 text-uppercase w-100 text-white fw-bold">
                                Đặt Hàng Ngay
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection