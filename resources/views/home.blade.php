@extends('layouts.app')

@section('title', 'Trang chủ - Electro Shop')

@section('content')

{{-- Carousel --}}
<div class="container-fluid carousel bg-light px-0">
    <div class="row g-0 justify-content-end">
        <div class="col-12 col-lg-7 col-xl-9">
            <div class="header-carousel owl-carousel bg-light py-5">
                <div class="row g-0 header-carousel-item align-items-center">
                    <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                        <img src="{{ asset('img/carousel-1.png') }}" class="img-fluid w-100" alt="">
                    </div>
                    <div class="col-xl-6 carousel-content p-4">
                        <h4 class="text-uppercase fw-bold mb-4 wow fadeInRight" style="letter-spacing: 3px;">Save Up To $400</h4>
                        <h1 class="display-3 text-capitalize mb-4 wow fadeInRight" data-wow-delay="0.3s">On Selected Laptops & Smartphones</h1>
                        <a class="btn btn-primary rounded-pill py-3 px-5 wow fadeInRight" data-wow-delay="0.7s"
                            href="{{ route('shop.index') }}">Shop Now</a>
                    </div>
                </div>
                <div class="row g-0 header-carousel-item align-items-center">
                    <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                        <img src="{{ asset('img/carousel-2.png') }}" class="img-fluid w-100" alt="">
                    </div>
                    <div class="col-xl-6 carousel-content p-4">
                        <h4 class="text-uppercase fw-bold mb-4 wow fadeInRight" style="letter-spacing: 3px;">Save Up To $200</h4>
                        <h1 class="display-3 text-capitalize mb-4 wow fadeInRight" data-wow-delay="0.3s">On Selected Laptops & Smartphones</h1>
                        <a class="btn btn-primary rounded-pill py-3 px-5 wow fadeInRight" data-wow-delay="0.7s"
                            href="{{ route('shop.index') }}">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5 col-xl-3 wow fadeInRight" data-wow-delay="0.1s">
            <div class="carousel-header-banner h-100">
                <img src="{{ asset('img/header-img.jpg') }}" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="">
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
                        <h6 class="text-uppercase mb-2">Free Return</h6>
                        <p class="mb-0">30 days money back guarantee!</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.2s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fab fa-telegram-plane fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Free Shipping</h6>
                        <p class="mb-0">Free shipping on all orders</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.3s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-life-ring fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Support 24/7</h6>
                        <p class="mb-0">We support online 24 hrs a day</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.4s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-credit-card fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Gift Cards</h6>
                        <p class="mb-0">Receive gift on order over $50</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.5s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lock fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Secure Payment</h6>
                        <p class="mb-0">We Value Your Security</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.6s">
            <div class="p-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-blog fa-2x text-primary"></i>
                    <div class="ms-4">
                        <h6 class="text-uppercase mb-2">Online Service</h6>
                        <p class="mb-0">Free return within 30 days</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Our Products --}}
<div class="container-fluid product py-5">
    <div class="container py-5">
        <div class="tab-class">
            <div class="row g-4">
                <div class="col-lg-4 text-start wow fadeInLeft" data-wow-delay="0.1s">
                    <h1>Our Products</h1>
                </div>
            </div>
            <div class="row g-4 mt-2">
                @forelse($products ?? [] as $product)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="product-item rounded wow fadeInUp">
                            <div class="product-item-inner border rounded">
                                <div class="product-item-inner-item">
                                    <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-3.png') }}"
                                        class="img-fluid w-100 rounded-top" alt="{{ $product->name }}">
                                    @if($product->is_new)
                                        <div class="product-new">New</div>
                                    @endif
                                    <div class="product-details">
                                        <a href="{{ route('shop.show', $product->id) }}">
                                            <i class="fa fa-eye fa-1x"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="text-center rounded-bottom p-4">
                                    <a href="{{ route('shop.index', ['category' => $product->category_id]) }}"
                                        class="d-block mb-2">{{ $product->category->name ?? '' }}</a>
                                    <a href="{{ route('shop.show', $product->id) }}"
                                        class="d-block h4">{{ $product->name }}</a>
                                    @if($product->original_price)
                                        <del class="me-2 fs-5">${{ number_format($product->original_price, 2) }}</del>
                                    @endif
                                    <span class="text-primary fs-5">${{ number_format($product->price, 2) }}</span>
                                </div>
                            </div>
                            <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
                                <form action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4">
                                        <i class="fas fa-shopping-cart me-2"></i> Add To Cart
                                    </button>
                                </form>
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

@endsection