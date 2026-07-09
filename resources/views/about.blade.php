@extends('layouts.app')

@section('title', 'Về chúng tôi - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Về chúng tôi</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Về chúng tôi</li>
        </ol>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-12">
                    <h5 class="text-primary">Câu chuyện của Electro Shop</h5>
                    <h1 class="display-6 mb-4">Đồng hành cùng bạn trong thế giới công nghệ</h1>
                    <p class="mb-4 fs-5">
                        Electro Shop là cửa hàng trực tuyến chuyên cung cấp các sản phẩm điện tử, thiết bị công nghệ
                        chính hãng với mức giá hợp lý. Chúng tôi hướng đến trải nghiệm mua sắm nhanh chóng, minh bạch
                        và đáng tin cậy cho mọi khách hàng.
                    </p>
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="p-4 bg-light rounded h-100">
                                <i class="fas fa-shipping-fast fa-2x text-primary mb-3"></i>
                                <h5>Giao hàng nhanh chóng</h5>
                                <p class="mb-0 text-muted">Đặt hàng dễ dàng, theo dõi đơn hàng và nhận sản phẩm chỉ trong vài ngày.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 bg-light rounded h-100">
                                <i class="fas fa-shield-alt fa-2x text-primary mb-3"></i>
                                <h5>Sản phẩm chính hãng</h5>
                                <p class="mb-0 text-muted">Cam kết nguồn gốc rõ ràng, bảo hành đầy đủ theo chính sách nhà sản xuất.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 bg-light rounded h-100">
                                <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                                <h5>Hỗ trợ tận tâm</h5>
                                <p class="mb-0 text-muted">Đội ngũ tư vấn và chăm sóc khách hàng sẵn sàng hỗ trợ trước và sau khi mua.</p>
                            </div>
                        </div>
                    </div>
                    <p class="mb-0 text-muted">
                        Mọi góp ý hoặc thắc mắc, quý khách vui lòng liên hệ với chúng tôi qua trang
                        <a href="{{ route('contact') }}">Liên hệ</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
