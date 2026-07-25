<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Cửa hàng Điện máy Electro')</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="preconnect" href="https://use.fontawesome.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">

    {{-- Libraries --}}
    <link href="{{ asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    {{-- Bootstrap & Style --}}
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('styles')
</head>
<body>

    {{-- Spinner --}}
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Đang tải...</span>
        </div>
    </div>

    {{-- Header Kết Hợp (Topbar + Logo & Search) --}}
    <div class="container-fluid px-5 py-3 d-none border-bottom d-lg-block">
        <div class="row gx-0 align-items-center">
            
            <div class="col-lg-3 text-start">
                <a href="{{ route('home') }}" class="navbar-brand p-0">
                    <h1 class="display-5 text-primary m-0 logo-text">
                        <i class="fas fa-shopping-bag text-secondary me-2"></i>Electro
                    </h1>
                </a>
            </div>

            <div class="col-lg-5 text-center">
                <form action="{{ route('shop.index') }}" method="GET">
                    <div class="position-relative">
                        <div class="d-flex border rounded-pill">
                            <input class="form-control border-0 rounded-pill w-100 py-3 ps-4" type="text"
                                name="search" value="{{ request('search') }}" placeholder="Bạn đang tìm gì?">
                            <select class="form-select text-dark border-0 border-start rounded-0 py-3"
                                name="category" style="width: 200px;">
                                <option value="">Tất cả danh mục</option>
                                @foreach($categories ?? [] as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary rounded-pill py-3 px-4" style="border: 0;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4 text-end d-flex align-items-center justify-content-end">
                
                <div class="d-flex flex-column text-end me-4 pe-4 border-end">
                    <!-- <div class="d-inline-flex align-items-center justify-content-end mb-1" style="font-size: 13px;">
                        <a href="#" class="text-muted">Trợ giúp</a><span class="text-muted mx-2">/</span>
                        <a href="#" class="text-muted">Hỗ trợ</a><span class="text-muted mx-2">/</span>
                        <a href="#" class="text-muted">Liên hệ</a>
                    </div> -->
                    <div>
                        <small class="text-dark me-1">Hotline:</small>
                        <a href="#" class="text-muted fw-bold">(+012) 1234 567890</a>
                    </div>
                </div>

                <div class="d-inline-flex align-items-center">
                    <div class="dropdown me-3">
                        <a href="#" class="text-muted d-flex align-items-center justify-content-center text-decoration-none" data-bs-toggle="dropdown">
                            <span class="rounded-circle btn-md-square border"><i class="fa fa-user"></i></span>
                        </a>
                        <div class="dropdown-menu rounded dropdown-menu-end mt-2">
                            @auth
                                <a href="{{ route('profile') }}" class="dropdown-item">Tài khoản của tôi</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Đăng xuất</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="dropdown-item">Đăng nhập</a>
                                <a href="{{ route('register') }}" class="dropdown-item">Đăng ký</a>
                            @endauth
                            <!-- <a href="#" class="dropdown-item">Danh sách yêu thích</a>
                            <a href="{{ route('cart.index') }}" class="dropdown-item">Giỏ hàng</a> -->
                        </div>
                    </div>

                    <!-- <a href="#" class="text-muted d-flex align-items-center justify-content-center me-3">
                        <span class="rounded-circle btn-md-square border"><i class="fas fa-heart"></i></span>
                    </a> -->

                    <a href="{{ route('cart.index') }}" class="text-muted d-flex align-items-center justify-content-center text-decoration-none">
                        <span class="rounded-circle btn-md-square border"><i class="fas fa-shopping-cart"></i></span>
                        <!-- <span class="text-dark ms-2 fw-bold">
                            ${{ number_format(session('cart_total', 0), 2) }}
                        </span> -->
                    </a>
                </div>

            </div>
        </div>
    </div>

    {{-- Navbar (Giữ nguyên) --}}
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="navbar navbar-light position-relative" style="width: 250px;">
                    <button class="navbar-toggler border-0 fs-4 w-100 px-0 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#allCat">
                        <h4 class="m-0"><i class="fa fa-bars me-2"></i>Danh mục</h4>
                    </button>
                    <div class="collapse navbar-collapse rounded-bottom" id="allCat">
                        <div class="navbar-nav ms-auto py-0">
                            <ul class="list-unstyled categories-bars">
                                @foreach($categories ?? [] as $cat)
                                    <li>
                                        <div class="categories-bars-item">
                                            <a href="{{ route('shop.index', ['category' => $cat->id]) }}">
                                                {{ $cat->name }}
                                            </a>
                                            <span>({{ $cat->products_count ?? 0 }})</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
            <div class="col-12 col-lg-9">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="{{ route('home') }}" class="navbar-brand d-block d-lg-none">
                        <h1 class="display-5 text-secondary m-0 logo-text">
                            <i class="fas fa-shopping-bag text-white me-2"></i>Electro
                        </h1>
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="{{ route('home') }}" class="nav-item nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Trang chủ</a>
                            
                            {{-- Sửa 'shop.*' thành cụ thể 'shop.index' và 'shop.show' để không bị dính vào các trang khác --}}
                            <a href="{{ route('shop.index') }}" class="nav-item nav-link {{ request()->routeIs('shop.index', 'shop.show') ? 'active' : '' }}">Sản phẩm</a>
                            
                            <a href="{{ route('bestseller') }}" class="nav-item nav-link {{ request()->routeIs('bestseller') ? 'active' : '' }}">Bán chạy</a>
                            {{-- Thêm điều kiện active riêng cho mã giảm giá --}}
                            <a href="{{ route('shop.vouchers') }}" class="nav-item nav-link {{ request()->routeIs('shop.vouchers') ? 'active' : '' }}">Mã giảm giá</a>
                            <a href="{{ route('flash-sale') }}" class="nav-item nav-link {{ request()->routeIs('flash-sale') ? 'active' : '' }}">
                                ⚡ Flash Sale
                            </a>
                            <a href="{{ route('contact') }}" class="nav-item nav-link {{ request()->routeIs('contact') ? 'active' : '' }} me-2">Liên hệ</a>
                        </div>
                        </div>
                </nav>
            </div>
        </div>
    </div>

    {{-- Container cho thông báo popup (toast) góc phải trên - dùng chung cho mọi trang --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        @if(session('cart_message'))
            <div id="cart-toast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i>{{ session('cart_message') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>
    @if(session('cart_message'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var toastEl = document.getElementById('cart-toast');
                if (toastEl) {
                    new bootstrap.Toast(toastEl, { delay: 3000 }).show();
                }
            });
        </script>
    @endif

    {{-- Nội dung từng trang (Giữ nguyên) --}}
    @yield('content')

    {{-- Footer (Giữ nguyên) --}}
    <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
        <div class="container py-5">
            <div class="row g-4 rounded mb-5" style="background: rgba(255, 255, 255, .03);">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-map-marked-alt fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Địa chỉ</h4>
                        <p class="mb-2">123 Đường ABC, Hà Nội, Việt Nam</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-envelope-open-text fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Gửi Email</h4>
                        <p class="mb-2">info@electroshop.com</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-headset fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Điện thoại</h4>
                        <p class="mb-2">(+012) 3456 7890</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-globe fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Trang web</h4>
                        <p class="mb-2">www.electroshop.com</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center g-5">
                {{-- <div class="col-md-6 col-lg-6 col-xl-3">
                    <h4 class="text-primary mb-4">Nhận Bản Tin</h4>
                    <p class="mb-3">Đăng ký nhận thông tin khuyến mãi mới nhất từ chúng tôi.</p>
                    <div class="position-relative mx-auto rounded-pill">
                        <input class="form-control rounded-pill w-100 py-3 ps-4 pe-5" type="text" placeholder="Nhập email của bạn">
                        <button type="button" class="btn btn-primary rounded-pill position-absolute top-0 end-0 py-2 mt-2 me-2">Đăng ký</button>
                    </div>
                </div> --}}
                <div class="col-md-6 col-lg-4">
                    <h4 class="text-primary mb-4">Chăm Sóc Khách Hàng</h4>
                    <a href="{{ route('contact') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Liên hệ chúng tôi</a>
                    <a href="{{ route('return-policy') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Chính sách đổi trả</a>
                    @auth
                        <a href="{{ route('profile') }}#order-history" class="d-block"><i class="fas fa-angle-right me-2"></i> Lịch sử đơn hàng</a>
                        <a href="{{ route('profile') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Tài khoản của tôi</a>
                    @else
                        <a href="{{ route('login') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Lịch sử đơn hàng</a>
                        <a href="{{ route('login') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Đăng nhập</a>
                    @endauth
                </div>
                <div class="col-md-6 col-lg-4">
                    <h4 class="text-primary mb-4">Thông Tin</h4>
                    <a href="{{ route('about') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Về chúng tôi</a>
                    <a href="{{ route('privacy-policy') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Chính sách bảo mật</a>
                    <a href="{{ route('terms') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Điều khoản & Điều kiện</a>
                    <a href="{{ route('faq') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Câu hỏi thường gặp</a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <h4 class="text-primary mb-4">Tiện Ích Khác</h4>
                    <a href="{{ route('shop.index') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Tất cả sản phẩm</a>
                    {{-- <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> Danh sách yêu thích</a> --}}
                    <a href="{{ route('cart.index') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Giỏ hàng</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Copyright (Giữ nguyên) --}}
    {{-- <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-start mb-md-0">
                    <span class="text-white">
                        <i class="fas fa-copyright text-light me-2"></i>Electro Shop, Đã đăng ký bản quyền.
                    </span>
                </div>
                <div class="col-md-6 text-center text-md-end text-white">
                    {{ date('Y') }}
                </div>
            </div>
        </div>
    </div> --}}

    {{-- Back to Top (Giữ nguyên) --}}
    <a href="#" class="btn btn-primary btn-lg-square back-to-top"><i class="fa fa-arrow-up"></i></a>

    {{-- JavaScript (Giữ nguyên) --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>

    {{-- Thêm vào giỏ hàng (AJAX) dùng chung cho toàn bộ site + hiển thị popup góc phải trên --}}
    <script>
        function showCartToast(message, isSuccess = true) {
            var container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = 1080;
                document.body.appendChild(container);
            }

            var toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-white ' + (isSuccess ? 'bg-success' : 'bg-danger') + ' border-0';
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.innerHTML = '<div class="d-flex">' +
                    '<div class="toast-body"><i class="fas ' + (isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle') + ' me-2"></i>' + message + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div>';

            container.appendChild(toastEl);
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.add-to-cart')) return;

            var btn = e.target.closest('.add-to-cart');
            var productId = btn.getAttribute('data-id');
            if (!productId) return;

            var originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang thêm...';

            fetch("{{ route('cart.add') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    showCartToast(data.message || 'Đã thêm vào giỏ hàng!', true);
                    if (typeof updateCartCount === 'function') updateCartCount(data.cart_count);
                } else {
                    showCartToast(data.message || 'Có lỗi xảy ra!', false);
                }
            })
            .catch(function (error) {
                console.error(error);
                showCartToast('Có lỗi kết nối. Vui lòng thử lại!', false);
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    </script>

    @stack('scripts')
    @include('shop.partials.chatbot-widget')
</body>
</html>