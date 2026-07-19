@extends('layouts.app')

@section('title', 'Sản phẩm Bán chạy - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Sản phẩm Bán chạy</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-white">Bán chạy nhất</li>
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
                            <p class="mb-0">Trong 30 ngày!</p>
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
                            <p class="mb-0">Luôn sẵn sàng hỗ trợ</p>
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
                            <h6 class="text-uppercase mb-2">Dịch vụ uy tín</h6>
                            <p class="mb-0">Chính hãng 100%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.2s">
                    <a href="{{ route('flash-sale') }}" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                        <div>
                            <p class="text-muted mb-3">Săn deal Camera cực chất!</p>
                            <h3 class="text-primary">Camera Thông minh</h3>
                            <h1 class="display-3 text-secondary mb-0">Giảm <span class="text-primary fw-normal">40%</span></h1>
                        </div>
                        <img src="{{ asset('img/product-1.png') }}" class="img-fluid" style="max-height: 150px" alt="Khuyến mãi Camera">
                    </a>
                </div>
                <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.3s">
                    <a href="{{ route('flash-sale') }}" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                        <div>
                            <p class="text-muted mb-3">Đồng hồ thông minh hiện đại!</p>
                            <h3 class="text-primary">Đồng hồ Thông minh</h3>
                            <h1 class="display-3 text-secondary mb-0">Giảm <span class="text-primary fw-normal">20%</span></h1>
                        </div>
                        <img src="{{ asset('img/product-2.png') }}" class="img-fluid" style="max-height: 150px" alt="Khuyến mãi Đồng hồ">
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid products pt-5">
    <div class="container products-mini py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 700px;">
            <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2 wow fadeInUp" data-wow-delay="0.1s">Sản Phẩm Bán Chạy</h4>
            <p class="mb-0 wow fadeInUp" data-wow-delay="0.2s">Khám phá ngay những sản phẩm công nghệ đang được yêu thích và săn lùng nhiều nhất.</p>
        </div>

        <div class="row g-4">
            @forelse($products as $product)
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.1s">
                <div class="products-mini-item border">
                    <div class="row g-0">
                        <div class="col-5">
                            <div class="products-mini-img border-end h-100">
                                <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-3.png') }}"
                                     class="img-fluid w-100 h-100" style="object-fit:cover" alt="{{ $product->name }}">
                                <div class="products-mini-icon rounded-circle bg-primary">
                                    <a href="{{ route('shop.show', $product->id) }}"><i class="fa fa-eye text-white"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="products-mini-content p-3">
                                <a href="{{ route('shop.index', ['category' => $product->category_id]) }}" class="d-block mb-2 text-muted">
                                    {{ $product->category->name ?? '' }}
                                </a>
                                <a href="{{ route('shop.show', $product->id) }}" class="d-block h5">{{ $product->name }}</a>
                                @if($product->is_flash_sale)
                                    <span class="badge bg-danger mb-1"><i class="fas fa-bolt me-1"></i>-{{ $product->flash_sale_discount_percent }}%</span><br>
                                    <del class="me-2 fs-6 text-muted">{{ number_format($product->price, 0, ',', '.') }}đ</del>
                                    <h5 class="text-danger fw-bold d-inline">{{ number_format($product->flash_sale_price, 0, ',', '.') }}đ</h5>
                                @else
                                    @if($product->original_price)
                                        <del class="me-2 fs-6 text-muted">{{ number_format($product->original_price, 0, ',', '.') }}đ</del>
                                    @endif
                                    <h5 class="text-primary fw-bold">{{ number_format($product->price, 0, ',', '.') }}đ</h5>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="products-mini-add border-top p-2 text-center">
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 add-to-cart" data-id="{{ $product->id }}">
                            <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <p class="text-muted">Chưa có sản phẩm nào được đánh dấu "Bán chạy".</p>
            </div>
            @endforelse
        </div>

        <div class="col-12 mt-5 d-flex justify-content-center" id="pagination">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

    <div class="container-fluid product pt-5">
        <div class="container py-5">
            <div class="tab-class">
                <div class="row g-4 mb-5">
                    <div class="col-lg-4 text-start wow fadeInLeft" data-wow-delay="0.1s">
                        <h1>Sản Phẩm Của Chúng Tôi</h1>
                    </div>
                    <div class="col-lg-8 text-end wow fadeInRight" data-wow-delay="0.1s">
                        <ul class="nav nav-pills d-inline-flex text-center mb-0">
                            <li class="nav-item mb-2">
                                <a class="d-flex mx-2 py-2 bg-light rounded-pill active" data-bs-toggle="pill" href="#tab-1">
                                    <span class="text-dark" style="width: 130px;">Tất cả</span>
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="d-flex py-2 mx-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-2">
                                    <span class="text-dark" style="width: 130px;">Hàng Mới Về</span>
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="d-flex mx-2 py-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-3">
                                    <span class="text-dark" style="width: 130px;">Nổi Bật</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="tab-content">
                    <div id="tab-1" class="tab-pane fade show p-0 active">
                        <div class="row g-4">
                            @forelse($allProducts ?? [] as $product)
                                @include('shop.partials.tab-product-card', ['product' => $product])
                            @empty
                                <div class="col-12 text-center py-5">
                                    <p class="text-muted">Chưa có sản phẩm nào.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div id="tab-2" class="tab-pane fade p-0">
                        <div class="row g-4">
                            @forelse($newArrivals ?? [] as $product)
                                @include('shop.partials.tab-product-card', ['product' => $product])
                            @empty
                                <div class="col-12 text-center py-5">
                                    <p class="text-muted">Chưa có hàng mới về.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div id="tab-3" class="tab-pane fade p-0">
                        <div class="row g-4">
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

    <div class="container-fluid py-5">
        <div class="container pb-5">
            <div class="row g-4">
                <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.1s">
                    <a href="{{ $cameraCategory ? route('shop.index', ['category' => $cameraCategory->id]) : route('shop.index') }}">
                        <div class="bg-primary rounded position-relative">
                            <img src="{{ asset('img/product-banner.jpg') }}" class="img-fluid w-100 rounded" alt="Banner máy ảnh">
                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center rounded p-4" style="background: rgba(255, 255, 255, 0.5);">
                                <h3 class="display-5 text-primary">Máy Ảnh Chuyên Nghiệp <br> <span>Kèm Phụ Kiện</span></h3>
                                <span class="btn btn-primary rounded-pill align-self-start py-2 px-4">Mua Ngay</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.2s">
                    <a href="{{ route('flash-sale') }}">
                        <div class="text-center bg-primary rounded position-relative">
                            <img src="{{ asset('img/product-banner-2.jpg') }}" class="img-fluid w-100" alt="Banner khuyến mãi">
                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center rounded p-4" style="background: rgba(242, 139, 0, 0.5);">
                                <h2 class="display-2 text-secondary">SALE KHỦNG</h2>
                                <h4 class="display-5 text-white mb-4">Giảm giá tới 50%</h4>
                                <span class="btn btn-secondary rounded-pill align-self-center py-2 px-4">Khám Phá Ngay</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection