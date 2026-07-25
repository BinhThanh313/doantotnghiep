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
                        @if($heroProduct)
                            <a href="{{ route('shop.index', ['category' => $heroProduct->category_id]) }}" class="d-block mb-2 text-primary">{{ $heroProduct->category->name ?? 'Sản phẩm' }}</a>
                            <a href="{{ route('shop.show', $heroProduct->id) }}" class="d-block text-white fs-3">{{ $heroProduct->name }}</a>
                            @if($heroProduct->is_flash_sale)
                                <span class="badge bg-danger mb-1"><i class="fas fa-bolt me-1"></i>Flash Sale -{{ $heroProduct->flash_sale_discount_percent }}%</span><br>
                                <del class="me-2 text-white fs-5">{{ number_format($heroProduct->price, 0, ',', '.') }}đ</del>
                                <span class="text-danger fs-5 fw-bold">{{ number_format($heroProduct->flash_sale_price, 0, ',', '.') }}đ</span>
                            @else
                                @if($heroProduct->original_price)
                                    <del class="me-2 text-white fs-5">{{ number_format($heroProduct->original_price, 0, ',', '.') }}đ</del>
                                @endif
                                <span class="text-primary fs-5">{{ number_format($heroProduct->price, 0, ',', '.') }}đ</span>
                            @endif
                        @else
                            <a href="{{ route('shop.index') }}" class="d-block mb-2 text-primary">Máy tính bảng</a>
                            <span class="d-block text-white fs-3">Đang cập nhật</span>
                        @endif
                    </div>
                    @if($heroProduct)
                        <button type="button" class="btn btn-primary rounded-pill py-2 px-4 add-to-cart" data-id="{{ $heroProduct->id }}">
                            <i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ
                        </button>
                    @endif
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
                <a href="{{ route('flash-sale') }}" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                    <div>
                        <p class="text-muted mb-3">Săn deal Camera cực chất!</p>
                        <h3 class="text-primary">Máy ảnh thông minh</h3>
                        <h1 class="display-3 text-secondary mb-0">Giảm <span class="text-primary fw-normal">40%</span></h1>
                    </div>
                    <img src="{{ asset('img/product-1.png') }}" class="img-fluid" style="max-height: 150px" alt="Camera Offer">
                </a>
            </div>
            <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.3s">
                <a href="{{ route('flash-sale') }}" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
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

@if(($forYou ?? collect())->isNotEmpty())
{{-- Gợi ý cá nhân hóa cho user đăng nhập (đẩy lên trước "Sản phẩm của chúng tôi") --}}
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
                {{-- Tab 1: Tất cả sản phẩm mới nhất --}}
                <div id="tab-1" class="tab-pane fade show p-0 active">
                    <div class="row g-4 mt-2">
                        @forelse($products ?? [] as $product)
                            @include('shop.partials.tab-product-card', ['product' => $product])
                        @empty
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Chưa có sản phẩm nào.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Tab 2: Hàng mới về (is_new = true) --}}
                <div id="tab-2" class="tab-pane fade p-0">
                    <div class="row g-4 mt-2">
                        @forelse($newArrivals ?? [] as $product)
                            @include('shop.partials.tab-product-card', ['product' => $product])
                        @empty
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Chưa có hàng mới về.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Tab 3: Nổi bật (xếp theo lượt xem) --}}
                <div id="tab-3" class="tab-pane fade p-0">
                    <div class="row g-4 mt-2">
                        @forelse($featuredProducts ?? [] as $product)
                            @include('shop.partials.tab-product-card', ['product' => $product])
                        @empty
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Chưa có sản phẩm nổi bật.</p>
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
                <a href="{{ $cameraCategory ? route('shop.index', ['category' => $cameraCategory->id]) : route('shop.index') }}">
                    <div class="bg-primary rounded position-relative">
                        <img src="{{ asset('img/product-banner.jpg') }}" class="img-fluid w-100 rounded" alt="Promo">
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center rounded p-4" style="background: rgba(255, 255, 255, 0.5);">
                            <h3 class="display-5 text-primary">Máy Ảnh EOS Rebel <br> <span>Kèm phụ kiện</span></h3>
                            <span class="btn btn-primary rounded-pill align-self-start py-2 px-4">Mua ngay</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.2s">
                <a href="{{ route('flash-sale') }}">
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
            @forelse($bestsellers ?? [] as $item)
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.1s">
                <div class="products-mini-item border rounded">
                    <div class="row g-0">
                        <div class="col-5">
                            <div class="products-mini-img border-end h-100">
                                <img src="{{ img_url($item->image, asset('img/product-3.png')) }}" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="{{ $item->name }}" loading="lazy">
                                <div class="products-mini-icon rounded-circle bg-primary">
                                    <a href="{{ route('shop.show', $item->id) }}"><i class="fa fa-eye text-white"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="products-mini-content p-3">
                                <a href="{{ route('shop.index', ['category' => $item->category_id]) }}" class="d-block mb-2 text-muted small">{{ $item->category->name ?? '' }}</a>
                                <a href="{{ route('shop.show', $item->id) }}" class="d-block h5">{{ $item->name }}</a>
                                <div class="d-flex mb-2 text-primary small">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                @if($item->is_flash_sale)
                                    <span class="badge bg-danger mb-1"><i class="fas fa-bolt me-1"></i>-{{ $item->flash_sale_discount_percent }}%</span><br>
                                    <del class="text-muted small me-1">{{ number_format($item->price, 0, ',', '.') }}đ</del>
                                    <h5 class="text-danger fw-bold d-inline">{{ number_format($item->flash_sale_price, 0, ',', '.') }}đ</h5>
                                @else
                                    <h5 class="text-primary fw-bold">{{ number_format($item->price, 0, ',', '.') }}đ</h5>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="products-mini-add border-top p-2 text-center">
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 add-to-cart" data-id="{{ $item->id }}">
                            <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <p class="text-muted">Chưa có sản phẩm bán chạy nào được đánh dấu.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- All Product Items Carousel --}}
<div class="container-fluid products productList overflow-hidden py-5">
    <div class="container products-mini py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 900px;">
            <h4 class="text-primary border-bottom border-primary border-2 d-inline-block p-2">Khám Phá</h4>
            <h1 class="mb-0 display-4">Tất cả sản phẩm</h1>
        </div>
        <div class="productList-carousel owl-carousel pt-4 wow fadeInUp" data-wow-delay="0.3s">
            @forelse($exploreProducts ?? [] as $product)
            <div class="productImg-item products-mini-item border rounded mx-2">
                <div class="row g-0">
                    <div class="col-5">
                        <div class="products-mini-img border-end h-100">
                            <img src="{{ img_url($product->image, asset('img/product-3.png')) }}" class="img-fluid w-100 h-100" alt="{{ $product->name }}" loading="lazy">
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="products-mini-content p-3">
                            <a href="{{ route('shop.index', ['category' => $product->category_id]) }}" class="d-block mb-1 text-muted small">{{ $product->category->name ?? '' }}</a>
                            <a href="{{ route('shop.show', $product->id) }}" class="d-block h6">{{ $product->name }}</a>
                            @if($product->is_flash_sale)
                                <span class="badge bg-danger">-{{ $product->flash_sale_discount_percent }}%</span>
                                <del class="text-muted small">{{ number_format($product->price, 0, ',', '.') }}đ</del>
                                <span class="text-danger d-block fw-bold">{{ number_format($product->flash_sale_price, 0, ',', '.') }}đ</span>
                            @else
                                @if($product->original_price)
                                    <del class="text-muted small">{{ number_format($product->original_price, 0, ',', '.') }}đ</del>
                                @endif
                                <span class="text-primary d-block fw-bold">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="products-mini-add border-top p-3 text-center">
                    <button type="button" class="btn btn-primary rounded-pill py-2 px-4 add-to-cart" data-id="{{ $product->id }}">
                        <i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
            @empty
            <p class="text-muted text-center w-100">Chưa có sản phẩm nào.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection