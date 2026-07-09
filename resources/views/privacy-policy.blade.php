@extends('layouts.app')

@section('title', 'Chính sách bảo mật - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Chính sách bảo mật</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Chính sách bảo mật</li>
        </ol>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <p class="text-muted">Cập nhật lần cuối: {{ now()->format('d/m/Y') }}</p>

                    <h4 class="text-primary mt-4">1. Thông tin chúng tôi thu thập</h4>
                    <p>
                        Khi đăng ký tài khoản, đặt hàng hoặc liên hệ với Electro Shop, chúng tôi có thể thu thập các
                        thông tin sau: họ tên, email, số điện thoại, địa chỉ giao hàng và lịch sử đơn hàng.
                    </p>

                    <h4 class="text-primary mt-4">2. Mục đích sử dụng thông tin</h4>
                    <p>Thông tin của khách hàng được sử dụng để:</p>
                    <ul>
                        <li>Xử lý và giao đơn hàng đúng địa chỉ, đúng thời gian.</li>
                        <li>Liên hệ xác nhận đơn hàng, hỗ trợ đổi trả và giải quyết khiếu nại.</li>
                        <li>Cải thiện trải nghiệm mua sắm và đề xuất sản phẩm phù hợp.</li>
                        <li>Gửi thông báo về khuyến mãi, voucher (nếu khách hàng đồng ý nhận thông tin).</li>
                    </ul>

                    <h4 class="text-primary mt-4">3. Bảo mật thông tin</h4>
                    <p>
                        Chúng tôi áp dụng các biện pháp kỹ thuật hợp lý để bảo vệ dữ liệu cá nhân khỏi truy cập,
                        chỉnh sửa hoặc tiết lộ trái phép. Mật khẩu tài khoản được mã hóa và không được lưu trữ dưới
                        dạng văn bản thuần.
                    </p>

                    <h4 class="text-primary mt-4">4. Chia sẻ thông tin với bên thứ ba</h4>
                    <p>
                        Electro Shop không bán hoặc trao đổi thông tin cá nhân của khách hàng cho bên thứ ba, ngoại
                        trừ trường hợp cần thiết để giao hàng (đơn vị vận chuyển) hoặc xử lý thanh toán (đối tác
                        thanh toán), hoặc khi pháp luật yêu cầu.
                    </p>

                    <h4 class="text-primary mt-4">5. Quyền của khách hàng</h4>
                    <p>
                        Khách hàng có quyền xem, chỉnh sửa hoặc yêu cầu xóa thông tin cá nhân của mình bằng cách
                        truy cập trang <a href="{{ route('profile') }}">Tài khoản của tôi</a> hoặc liên hệ với chúng
                        tôi qua trang <a href="{{ route('contact') }}">Liên hệ</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
