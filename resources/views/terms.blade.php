@extends('layouts.app')

@section('title', 'Điều khoản & Điều kiện - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Điều khoản & Điều kiện</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Điều khoản & Điều kiện</li>
        </ol>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <p class="text-muted">Cập nhật lần cuối: {{ now()->format('d/m/Y') }}</p>

                    <h4 class="text-primary mt-4">1. Chấp nhận điều khoản</h4>
                    <p>
                        Khi truy cập và sử dụng website Electro Shop, khách hàng đồng ý tuân thủ các điều khoản và
                        điều kiện được nêu dưới đây. Nếu không đồng ý, vui lòng ngừng sử dụng dịch vụ.
                    </p>

                    <h4 class="text-primary mt-4">2. Tài khoản người dùng</h4>
                    <p>
                        Khách hàng có trách nhiệm cung cấp thông tin chính xác khi đăng ký tài khoản và bảo mật
                        thông tin đăng nhập của mình. Electro Shop không chịu trách nhiệm cho các thiệt hại phát
                        sinh từ việc lộ thông tin tài khoản do lỗi của khách hàng.
                    </p>

                    <h4 class="text-primary mt-4">3. Đặt hàng và thanh toán</h4>
                    <p>
                        Đơn hàng được xác nhận sau khi hệ thống ghi nhận đầy đủ thông tin giao hàng và phương thức
                        thanh toán hợp lệ (thanh toán khi nhận hàng hoặc chuyển khoản ngân hàng). Electro Shop có
                        quyền từ chối hoặc hủy đơn hàng trong trường hợp phát hiện gian lận hoặc sai sót về giá,
                        tồn kho.
                    </p>

                    <h4 class="text-primary mt-4">4. Giá cả và khuyến mãi</h4>
                    <p>
                        Giá sản phẩm, chương trình flash sale và mã giảm giá (voucher) có thể thay đổi mà không cần
                        báo trước. Mỗi voucher chỉ áp dụng theo đúng điều kiện được công bố tại thời điểm sử dụng.
                    </p>

                    <h4 class="text-primary mt-4">5. Giới hạn trách nhiệm</h4>
                    <p>
                        Electro Shop nỗ lực đảm bảo thông tin sản phẩm chính xác nhưng không đảm bảo tuyệt đối
                        không có sai sót. Trong mọi trường hợp, trách nhiệm của Electro Shop giới hạn trong giá trị
                        đơn hàng liên quan.
                    </p>

                    <h4 class="text-primary mt-4">6. Thay đổi điều khoản</h4>
                    <p>
                        Chúng tôi có thể cập nhật các điều khoản này theo thời gian. Phiên bản mới nhất luôn được
                        đăng tải tại trang này.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
