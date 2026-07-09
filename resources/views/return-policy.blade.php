@extends('layouts.app')

@section('title', 'Chính sách đổi trả - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Chính sách đổi trả</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Chính sách đổi trả</li>
        </ol>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h4 class="text-primary mt-4">1. Điều kiện đổi trả</h4>
                    <ul>
                        <li>Sản phẩm còn nguyên tem, nhãn mác, chưa qua sử dụng và còn đầy đủ phụ kiện, hộp đựng.</li>
                        <li>Yêu cầu đổi trả được gửi trong vòng 7 ngày kể từ ngày nhận hàng.</li>
                        <li>Sản phẩm bị lỗi do nhà sản xuất, giao sai mẫu mã/số lượng, hoặc hư hỏng trong quá trình vận chuyển.</li>
                    </ul>

                    <h4 class="text-primary mt-4">2. Trường hợp không áp dụng đổi trả</h4>
                    <ul>
                        <li>Sản phẩm hư hỏng do lỗi sử dụng của khách hàng.</li>
                        <li>Sản phẩm đã qua sử dụng, mất tem bảo hành hoặc không còn nguyên vẹn phụ kiện đi kèm.</li>
                        <li>Sản phẩm thuộc danh mục khuyến mãi đặc biệt có ghi chú "không áp dụng đổi trả".</li>
                    </ul>

                    <h4 class="text-primary mt-4">3. Quy trình đổi trả</h4>
                    <ol>
                        <li>Truy cập <a href="{{ route('profile') }}#order-history">Lịch sử đơn hàng</a> và chọn đơn hàng cần đổi trả.</li>
                        <li>Liên hệ với Electro Shop qua trang <a href="{{ route('contact') }}">Liên hệ</a> kèm mã đơn hàng và lý do đổi trả.</li>
                        <li>Đội ngũ hỗ trợ xác nhận và hướng dẫn gửi trả sản phẩm.</li>
                        <li>Sau khi nhận và kiểm tra sản phẩm, chúng tôi tiến hành đổi mới hoặc hoàn tiền theo phương thức thanh toán ban đầu.</li>
                    </ol>

                    <h4 class="text-primary mt-4">4. Thời gian xử lý</h4>
                    <p>
                        Thời gian xử lý đổi trả thông thường từ 3–7 ngày làm việc kể từ khi Electro Shop nhận được
                        sản phẩm hoàn trả, tùy thuộc vào đơn vị vận chuyển.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
