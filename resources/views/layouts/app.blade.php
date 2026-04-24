<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Electro Shop')</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Libraries --}}
    <link href="{{ asset('lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    {{-- Bootstrap & Style --}}
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>

    {{-- Spinner --}}
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    {{-- Topbar --}}
    <div class="container-fluid px-5 d-none border-bottom d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-4 text-center text-lg-start mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a href="#" class="text-muted me-2">Help</a><small> / </small>
                    <a href="#" class="text-muted mx-2">Support</a><small> / </small>
                    <a href="#" class="text-muted ms-2">Contact</a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-flex align-items-center justify-content-center">
                <small class="text-dark">Call Us:</small>
                <a href="#" class="text-muted">(+012) 1234 567890</a>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted ms-2" data-bs-toggle="dropdown">
                            <small><i class="fa fa-home me-2"></i> My Dashboard</small>
                        </a>
                        <div class="dropdown-menu rounded">
                            @auth
                                <a href="{{ route('profile') }}" class="dropdown-item">My Account</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Log Out</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="dropdown-item">Login</a>
                                <a href="{{ route('register') }}" class="dropdown-item">Register</a>
                            @endauth
                            <a href="#" class="dropdown-item">Wishlist</a>
                            <a href="{{ route('cart.index') }}" class="dropdown-item">My Cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Logo & Search --}}
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <a href="{{ route('home') }}" class="navbar-brand p-0">
                    <h1 class="display-5 text-primary m-0">
                        <i class="fas fa-shopping-bag text-secondary me-2"></i>Electro
                    </h1>
                </a>
            </div>
            <div class="col-md-4 col-lg-6 text-center">
                <form action="{{ route('shop.index') }}" method="GET">
                    <div class="position-relative ps-4">
                        <div class="d-flex border rounded-pill">
                            <input class="form-control border-0 rounded-pill w-100 py-3" type="text"
                                name="search" value="{{ request('search') }}" placeholder="Search Looking For?">
                            <select class="form-select text-dark border-0 border-start rounded-0 p-3"
                                name="category" style="width: 200px;">
                                <option value="">All Category</option>
                                @foreach($categories ?? [] as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary rounded-pill py-3 px-5" style="border: 0;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4 col-lg-3 text-center text-lg-end">
                <div class="d-inline-flex align-items-center">
                    <a href="#" class="text-muted d-flex align-items-center justify-content-center me-3">
                        <span class="rounded-circle btn-md-square border"><i class="fas fa-heart"></i></span>
                    </a>
                    <a href="{{ route('cart.index') }}" class="text-muted d-flex align-items-center justify-content-center">
                        <span class="rounded-circle btn-md-square border"><i class="fas fa-shopping-cart"></i></span>
                        <span class="text-dark ms-2">
                            ${{ number_format(session('cart_total', 0), 2) }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Navbar --}}
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="navbar navbar-light position-relative" style="width: 250px;">
                    <button class="navbar-toggler border-0 fs-4 w-100 px-0 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#allCat">
                        <h4 class="m-0"><i class="fa fa-bars me-2"></i>All Categories</h4>
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
                        <h1 class="display-5 text-secondary m-0">
                            <i class="fas fa-shopping-bag text-white me-2"></i>Electro
                        </h1>
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="{{ route('home') }}" class="nav-item nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                            <a href="{{ route('shop.index') }}" class="nav-item nav-link {{ request()->routeIs('shop.*') ? 'active' : '' }}">Shop</a>
                            <a href="{{ route('contact') }}" class="nav-item nav-link me-2">Contact</a>
                        </div>
                        <a href="tel:+01234567890" class="btn btn-secondary rounded-pill py-2 px-4 px-lg-3 mb-3 mb-md-3 mb-lg-0">
                            <i class="fa fa-mobile-alt me-2"></i> +0123 456 7890
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    {{-- Nội dung từng trang --}}
    @yield('content')

    {{-- Footer --}}
    <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
        <div class="container py-5">
            <div class="row g-4 rounded mb-5" style="background: rgba(255, 255, 255, .03);">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Address</h4>
                        <p class="mb-2">123 Street, Hanoi, Vietnam</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fas fa-envelope fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Mail Us</h4>
                        <p class="mb-2">info@electroshop.com</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fa fa-phone-alt fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Telephone</h4>
                        <p class="mb-2">(+012) 3456 7890</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded p-4">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width: 70px; height: 70px;">
                            <i class="fab fa-firefox-browser fa-2x text-primary"></i>
                        </div>
                        <h4 class="text-white">Website</h4>
                        <p class="mb-2">www.electroshop.com</p>
                    </div>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h4 class="text-primary mb-4">Newsletter</h4>
                    <p class="mb-3">Đăng ký nhận thông tin khuyến mãi mới nhất từ chúng tôi.</p>
                    <div class="position-relative mx-auto rounded-pill">
                        <input class="form-control rounded-pill w-100 py-3 ps-4 pe-5" type="text" placeholder="Enter your email">
                        <button type="button" class="btn btn-primary rounded-pill position-absolute top-0 end-0 py-2 mt-2 me-2">SignUp</button>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h4 class="text-primary mb-4">Customer Service</h4>
                    <a href="{{ route('contact') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Contact Us</a>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> Returns</a>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> Order History</a>
                    @auth
                        <a href="{{ route('profile') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> My Account</a>
                    @else
                        <a href="{{ route('login') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> Login</a>
                    @endauth
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h4 class="text-primary mb-4">Information</h4>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> About Us</a>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> Privacy Policy</a>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> Terms & Conditions</a>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> FAQ</a>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h4 class="text-primary mb-4">Extras</h4>
                    <a href="{{ route('shop.index') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> All Products</a>
                    <a href="#" class="d-block"><i class="fas fa-angle-right me-2"></i> Wishlist</a>
                    <a href="{{ route('cart.index') }}" class="d-block"><i class="fas fa-angle-right me-2"></i> My Cart</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Copyright --}}
    <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-start mb-md-0">
                    <span class="text-white">
                        <i class="fas fa-copyright text-light me-2"></i>Electro Shop, All rights reserved.
                    </span>
                </div>
                <div class="col-md-6 text-center text-md-end text-white">
                    {{ date('Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Back to Top --}}
    <a href="#" class="btn btn-primary btn-lg-square back-to-top"><i class="fa fa-arrow-up"></i></a>

    {{-- JavaScript --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('lib/wow/wow.min.js') }}"></script>
    <script src="{{ asset('lib/owlcarousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>

    @stack('scripts')
</body>
</html>