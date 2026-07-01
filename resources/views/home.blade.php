@extends('layouts.app')

@section('title', 'Trang chủ - Electro Shop')

@section('content')

{{-- Carousel & Header Banner --}}
<div class="container-fluid carousel bg-light px-0">
    <div class="row g-0 justify-content-end">
        <div class="col-12 col-lg-7 col-xl-9">
            <div class="header-carousel owl-carousel bg-light py-5">
                <div class="row g-0 header-carousel-item align-items-center">
                    <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                        <img src="{{ asset('img/carousel-1.png') }}" class="img-fluid w-100" alt="Carousel 1">
                    </div>
                    <div class="col-xl-6 carousel-content p-4">
                        <h4 class="text-uppercase fw-bold mb-4 wow fadeInRight" style="letter-spacing: 3px;">Tiết kiệm tới 10.000.000đ</h4>
                        <h1 class="display-3 text-capitalize mb-4 wow fadeInRight" data-wow-delay="0.3s">Cho các mẫu Laptop & Điện thoại chọn lọc</h1>
                        <a class="btn btn-primary rounded-pill py-3 px-5 wow fadeInRight" data-wow-delay="0.7s"
                            href="{{ route('shop.index') }}">Mua ngay</a>
                    </div>
                </div>
                <div class="row g-0 header-carousel-item align-items-center">
                    <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                        <img src="{{ asset('img/carousel-2.png') }}" class="img-fluid w-100" alt="Carousel 2">
                    </div>
                    <div class="col-xl-6 carousel-content p-4">
                        <h4 class="text-uppercase fw-bold mb-4 wow fadeInRight" style="letter-spacing: 3px;">Ưu đãi tới 5.000.000đ</h4>
                        <h1 class="display-3 text-capitalize mb-4 wow fadeInRight" data-wow-delay="0.3s">Sắm Smartphone & Đồ công nghệ giá cực hời</h1>
                        <a class="btn btn-primary rounded-pill py-3 px-5 wow fadeInRight" data-wow-delay="0.7s"
                            href="{{ route('shop.index') }}">Mua ngay</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5 col-xl-3 wow fadeInRight" data-wow-delay="0.1s">
            <div class="carousel-header-banner h-100 position-relative">
                <img src="{{ asset('img/header-img.jpg') }}" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="Banner phụ">
                <div class="carousel-banner-offer">
                    <p class="bg-primary text-white rounded fs-5 py-2 px-4 mb-0 me-3">Tiết kiệm 1.200.000đ</p>
                    <p class="text-primary fs-5 fw-bold mb-0">Ưu đãi đặc biệt</p>
                </div>
                <div class="carousel-banner">
                    <div class="carousel-banner-content text-center p-4">
                        <a href="#" class="d-block mb-2 text-primary">Máy tính bảng</a>
                        <a href="#" class="d-block text-white fs-3">Apple iPad Mini <br> G2356</a>
                        <del class="me-2 text-white fs-5">15.000.000đ</del>
                        <span class="text-primary fs-5">13.800.000đ</span>
                    </div>
                    <a href="#" class="btn btn-primary rounded-pill py-2 px-4">
                        <i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Services --}}
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
                        <h6 class="text-uppercase mb-2">Miễn phí Ship</h6>
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
                        <p class="mb-0">Sẵn sàng hỗ trợ bạn</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.4s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-credit-card fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Quà tặng lớn</h6>
                        <p class="mb-0">Đơn hàng trên 1.000.000đ</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.5s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lock fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Bảo mật cao</h6>
                        <p class="mb-0">Thanh toán an toàn</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.6s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-blog fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Dịch vụ uy tín</h6>
                        <p class="mb-0">Cam kết chính hãng</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('shop.partials.flash-sale-widget')
{{-- Middle Offer Banners --}}
<div class="container-fluid bg-light py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.2s">
                <a href="#" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                    <div>
                        <p class="text-muted mb-3">Săn deal Camera cực chất!</p>
                        <h3 class="text-primary">Máy ảnh thông minh</h3>
                        <h1 class="display-3 text-secondary mb-0">Giảm <span class="text-primary fw-normal">40%</span></h1>
                    </div>
                    <img src="{{ asset('img/product-1.png') }}" class="img-fluid" style="max-height: 150px" alt="Camera Offer">
                </a>
            </div>
            <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.3s">
                <a href="#" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                    <div>
                        <p class="text-muted mb-3">Đồng hồ hiện đại cho bạn!</p>
                        <h3 class="text-primary">Đồng hồ thông minh</h3>
                        <h1 class="display-3 text-secondary mb-0">Giảm <span class="text-primary fw-normal">20%</span></h1>
                    </div>
                    <img src="{{ asset('img/product-2.png') }}" class="img-fluid" style="max-height: 150px" alt="Watch Offer">
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Our Products with Tabs --}}
<div class="container-fluid product py-5">
    <div class="container py-5">
        <div class="tab-class">
            <div class="row g-4">
                <div class="col-lg-4 text-start wow fadeInLeft" data-wow-delay="0.1s">
                    <h1>Sản phẩm của chúng tôi</h1>
                </div>
                <div class="col-lg-8 text-end wow fadeInRight" data-wow-delay="0.1s">
                    <ul class="nav nav-pills d-inline-flex text-center mb-5">
                        <li class="nav-item mb-4">
                            <a class="d-flex mx-2 py-2 bg-light rounded-pill active" data-bs-toggle="pill" href="#tab-1">
                                <span class="text-dark" style="width: 130px;">Tất cả</span>
                            </a>
                        </li>
                        <li class="nav-item mb-4">
                            <a class="d-flex py-2 mx-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-2">
                                <span class="text-dark" style="width: 130px;">Hàng mới về</span>
                            </a>
                        </li>
                        <li class="nav-item mb-4">
                            <a class="d-flex mx-2 py-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-3">
                                <span class="text-dark" style="width: 130px;">Nổi bật</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tab-content">
                <div id="tab-1" class="tab-pane fade show p-0 active">
                    <div class="row g-4 mt-2">
                        @forelse($products ?? [] as $product)
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="product-item rounded wow fadeInUp">
                                    <div class="product-item-inner border rounded">
                                        <div class="product-item-inner-item">
                                            <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-3.png') }}"
                                                class="img-fluid w-100 rounded-top" alt="{{ $product->name }}">
                                            @if($product->is_new)
                                                <div class="product-new">Mới</div>
                                            @endif
                                            <div class="product-details">
                                                <a href="{{ route('shop.show', $product->id) }}">
                                                    <i class="fa fa-eye fa-1x"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="text-center rounded-bottom p-4">
                                            <a href="{{ route('shop.index', ['category' => $product->category_id]) }}"
                                                class="d-block mb-2 text-muted">{{ $product->category->name ?? '' }}</a>
                                            <a href="{{ route('shop.show', $product->id) }}"
                                                class="d-block h4">{{ $product->name }}</a>
                                            @if($product->original_price)
                                                <del class="me-2 fs-5 text-muted">{{ number_format($product->original_price, 0, ',', '.') }}đ</del>
                                            @endif
                                            <span class="text-primary fs-5 fw-bold">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                                        </div>
                                    </div>
                                    <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
                                        <form action="{{ route('cart.add') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4 w-100">
                                                <i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ
                                            </button>
                                        </form>
                                        <div class="d-flex justify-content-center">
                                            <div class="d-flex text-primary">
                                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Chưa có sản phẩm nào.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bottom Promo Banners --}}
<div class="container-fluid py-5">
    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.1s">
                <a href="#">
                    <div class="bg-primary rounded position-relative">
                        <img src="{{ asset('img/product-banner.jpg') }}" class="img-fluid w-100 rounded" alt="Promo">
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center rounded p-4" style="background: rgba(255, 255, 255, 0.5);">
                            <h3 class="display-5 text-primary">Máy Ảnh EOS Rebel <br> <span>Kèm phụ kiện</span></h3>
                            <p class="fs-4 text-dark fw-bold">18.990.000đ</p>
                            <span class="btn btn-primary rounded-pill align-self-start py-2 px-4">Mua ngay</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.2s">
                <a href="#">
                    <div class="text-center bg-primary rounded position-relative">
                        <img src="{{ asset('img/product-banner-2.jpg') }}" class="img-fluid w-100" alt="Sale">
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center rounded p-4" style="background: rgba(242, 139, 0, 0.5);">
                            <h2 class="display-2 text-secondary">SALE KHỦNG</h2>
                            <h4 class="display-5 text-white mb-4">Giảm giá lên tới 50%</h4>
                            <span class="btn btn-secondary rounded-pill align-self-center py-2 px-4">Khám phá ngay</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Bestseller Products --}}
<div class="container-fluid products pb-5">
    <div class="container products-mini py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 700px;">
            <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2">Bán Chạy Nhất</h4>
            <p class="mb-0">Những sản phẩm được khách hàng tin dùng và lựa chọn nhiều nhất tại Electro Shop.</p>
        </div>
        <div class="row g-4">
            {{-- Sau này lặp @foreach($bestsellers as $item) --}}
            @for ($i = 3; $i <= 8; $i++) 
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.1s">
                <div class="products-mini-item border rounded">
                    <div class="row g-0">
                        <div class="col-5">
                            <div class="products-mini-img border-end h-100">
                                <img src="{{ asset('img/product-'.$i.'.png') }}" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="Bestseller">
                                <div class="products-mini-icon rounded-circle bg-primary">
                                    <a href="#"><i class="fa fa-eye text-white"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="products-mini-content p-3">
                                <a href="#" class="d-block mb-2 text-muted small">Điện tử</a>
                                <a href="#" class="d-block h5">Sản phẩm Bán chạy {{ $i }}</a>
                                <div class="d-flex mb-2 text-primary small">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <h5 class="text-primary fw-bold">2.500.000đ</h5>
                            </div>
                        </div>
                    </div>
                    <div class="products-mini-add border-top p-2 text-center">
                        <a href="#" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ
                        </a>
                    </div>
                </div>
            </div>
            @endfor
        </div>
    </div>
</div>

@if(($forYou ?? collect())->isNotEmpty())
{{-- Gợi ý cá nhân hóa cho user đăng nhập --}}
<div class="container-fluid products pb-5">
    <div class="container products-mini py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 700px;">
            <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2">
                <i class="fas fa-user-check me-2"></i>Gợi Ý Dành Riêng Cho Bạn
            </h4>
            <p class="mb-0">Dựa trên lịch sử mua sắm và sản phẩm bạn đã xem tại Electro Shop.</p>
        </div>
        <div class="row g-4">
            @foreach($forYou as $p)
                @include('shop.partials.product-card', ['product' => $p])
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- All Product Items Carousel --}}
<div class="container-fluid products productList overflow-hidden py-5">
    <div class="container products-mini py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 900px;">
            <h4 class="text-primary border-bottom border-primary border-2 d-inline-block p-2">Khám Phá</h4>
            <h1 class="mb-0 display-4">Tất cả sản phẩm</h1>
        </div>
        <div class="productList-carousel owl-carousel pt-4 wow fadeInUp" data-wow-delay="0.3s">
            {{-- Lặp sản phẩm ở đây --}}
            @for ($i = 4; $i <= 10; $i++)
            <div class="productImg-item products-mini-item border rounded mx-2">
                <div class="row g-0">
                    <div class="col-5">
                        <div class="products-mini-img border-end h-100">
                            <img src="{{ asset('img/product-'.$i.'.png') }}" class="img-fluid w-100 h-100" alt="Product">
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="products-mini-content p-3">
                            <a href="#" class="d-block mb-1 text-muted small">Công nghệ</a>
                            <a href="#" class="d-block h6">Thiết bị đời mới {{ $i }}</a>
                            <del class="text-muted small">5.000.000đ</del>
                            <span class="text-primary d-block fw-bold">4.200.000đ</span>
                        </div>
                    </div>
                </div>
                <div class="products-mini-add border-top p-2 text-center">
                    <a href="#" class="text-primary"><i class="fas fa-shopping-cart me-2"></i>Giỏ hàng</a>
                </div>
            </div>
            @endfor
        </div>
    </div>
</div>
@endsection