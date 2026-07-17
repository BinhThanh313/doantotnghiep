@extends('layouts.app')

@section('title', $product->name . ' - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Chi tiết sản phẩm</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-white">{{ $product->name }}</li>
        </ol>
    </div>
    
    <div class="container-fluid shop py-5">
        <div class="container py-5">
            <div class="row g-4">
                
                <div class="col-lg-5 col-xl-3 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="input-group w-100 mx-auto d-flex mb-4">
                        <input type="search" class="form-control p-3" placeholder="Tìm kiếm..." aria-describedby="search-icon-1">
                        <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                    </div>
                    
                    <div class="product-categories mb-4">
                        <h4>Danh mục sản phẩm</h4>
                        <ul class="list-unstyled">
                            {{-- Lấy danh sách Categories nếu có truyền từ Controller sang --}}
                            @if(isset($categories))
                                @foreach($categories as $cat)
                                <li>
                                    <div class="categories-item">
                                        <a href="{{ route('shop.index', ['category' => $cat->id]) }}" class="text-dark">
                                            <i class="fas fa-apple-alt text-secondary me-2"></i>{{ $cat->name }}
                                        </a>
                                        <span>({{ $cat->products_count ?? 0 }})</span>
                                    </div>
                                </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                    
                    <a href="#">
                        <div class="position-relative">
                            <img src="{{ asset('img/product-banner-2.jpg') }}" class="img-fluid w-100 rounded" alt="Khuyến mãi">
                            <div class="text-center position-absolute d-flex flex-column align-items-center justify-content-center rounded p-4"
                                style="width: 100%; height: 100%; top: 0; right: 0; background: rgba(242, 139, 0, 0.3);">
                                <h5 class="display-6 text-primary">KHUYẾN MÃI</h5>
                                <h4 class="text-secondary">Giảm giá 50%</h4>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-lg-7 col-xl-9 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="row g-4 single-product">
                        <div class="col-xl-6">
                            <div class="single-inner bg-light rounded p-2">
                                <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-4.png') }}" class="img-fluid rounded w-100" alt="{{ $product->name }}">
                            </div>
                        </div>
                        
                        <div class="col-xl-6">
                            <h4 class="fw-bold mb-3">{{ $product->name }}</h4>
                            <p class="mb-3">Danh mục: {{ $product->category->name ?? 'Đang cập nhật' }}</p>
                            
                            {{-- Đã sửa định dạng tiền sang VNĐ --}}
                            @if($product->is_flash_sale)
                                <div class="mb-2">
                                    <span class="badge bg-danger fs-6"><i class="fas fa-bolt me-1"></i>Flash Sale -{{ $product->flash_sale_discount_percent }}%</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <h5 class="fw-bold mb-0 text-danger">{{ number_format($product->flash_sale_price, 0, ',', '.') }}đ</h5>
                                    <del class="text-muted">{{ number_format($product->price, 0, ',', '.') }}đ</del>
                                </div>
                            @else
                                <h5 class="fw-bold mb-3 text-primary">{{ number_format($product->price, 0, ',', '.') }}đ</h5>
                            @endif
                            
                            <div class="d-flex mb-4">
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star"></i>
                            </div>
                            
                            <div class="d-flex flex-column mb-3">
                                <small>Tình trạng: <strong class="text-primary">Còn hàng</strong></small>
                            </div>
                            
                            <p class="mb-4">Sản phẩm chính hãng. Cam kết chất lượng và bảo hành đầy đủ. Phù hợp cho nhu cầu sử dụng của bạn.</p>
                            
                            <form action="{{ route('cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                
                                <div class="input-group quantity mb-4" style="width: 130px;">
                                    <div class="input-group-btn">
                                        <button type="button" class="btn btn-sm btn-minus rounded-circle bg-light border">
                                            <i class="fa fa-minus"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="quantity" class="form-control form-control-sm text-center border-0" value="1">
                                    <div class="input-group-btn">
                                        <button type="button" class="btn btn-sm btn-plus rounded-circle bg-light border">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary border border-secondary rounded-pill px-4 py-2 text-white">
                                    <i class="fa fa-shopping-bag me-2"></i> Thêm vào giỏ hàng
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-lg-12 mt-5">
                            <nav>
                                <div class="nav nav-tabs mb-3">
                                    <button class="nav-link active border-white border-bottom-0" type="button" role="tab" id="nav-about-tab" data-bs-toggle="tab" data-bs-target="#nav-about" aria-controls="nav-about" aria-selected="true">Mô tả sản phẩm</button>
                                    @if($product->specifications->isNotEmpty())
                                    <button class="nav-link border-white border-bottom-0" type="button" role="tab" id="nav-specs-tab" data-bs-toggle="tab" data-bs-target="#nav-specs" aria-controls="nav-specs" aria-selected="false">Thông số kỹ thuật</button>
                                    @endif
                                    <button class="nav-link border-white border-bottom-0" type="button" role="tab" id="nav-mission-tab" data-bs-toggle="tab" data-bs-target="#nav-mission" aria-controls="nav-mission" aria-selected="false">Đánh giá (<span id="review-tab-count">0</span>)</button>
                                </div>
                            </nav>
                            <div class="tab-content mb-5">
                                <div class="tab-pane active" id="nav-about" role="tabpanel" aria-labelledby="nav-about-tab">
                                    <p>{!! $product->description ?? 'Chưa có mô tả cho sản phẩm này.' !!}</p>
                                </div>
                                @if($product->specifications->isNotEmpty())
                                <div class="tab-pane" id="nav-specs" role="tabpanel" aria-labelledby="nav-specs-tab">
                                    @foreach($product->specifications_grouped as $groupName => $items)
                                        <h6 class="fw-bold text-primary mt-4 mb-2">{{ $groupName }}</h6>
                                        <table class="table table-bordered table-striped align-middle mb-0">
                                            <tbody>
                                                @foreach($items as $item)
                                                <tr>
                                                    <td style="width: 35%;" class="fw-semibold">{{ $item['label'] }}</td>
                                                    <td style="white-space: pre-line;">{{ $item['value'] }}{{ $item['unit'] ? ' ' . $item['unit'] : '' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endforeach
                                </div>
                                @endif
                                <div class="tab-pane" id="nav-mission" role="tabpanel" aria-labelledby="nav-mission-tab">
                                        @include('shop.partials.review-list')
                                        
                                        @auth
                                            @include('shop.partials.review-form')
                                        @else
                                            <div class="alert alert-info mt-4">
                                                Vui lòng <a href="{{ route('login') }}" class="fw-bold">đăng nhập</a> để gửi đánh giá sản phẩm.
                                            </div>
                                        @endauth
                                    </div>

                                    @push('scripts')
                                    <script>
                                        // Truyền biến productId và base URL từ Blade sang JS
                                        const currentProductId = {{ $product->id }};
                                        const appBaseUrl = "{{ rtrim(asset('/'), '/') }}";
                                    </script>
                                    <script src="{{ asset('js/review.js') }}"></script>
                                    @endpush
                            </div>
                        </div>

                        @include('shop.partials.combo-suggestion')

                        @include('shop.partials.recommendations')

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection