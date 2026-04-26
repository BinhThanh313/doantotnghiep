@extends('layouts.app')

@section('title', 'Cửa hàng - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Shop Page</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active text-white">Shop</li>
    </ol>
</div>

<div class="container-fluid shop py-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3 wow fadeInUp" data-wow-delay="0.1s">
                <div class="product-categories mb-4">
                    <h4>Danh mục sản phẩm</h4>
                    <ul class="list-unstyled">
                        @foreach($categories as $cat)
                        <li>
                            <div class="categories-item">
                                <a href="{{ route('shop.index', ['category' => $cat->id]) }}" class="text-dark">
                                    <i class="fas fa-apple-alt text-secondary me-2"></i>{{ $cat->name }}
                                </a>
                                <span>({{ $cat->products_count }})</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                </div>

            <div class="col-lg-9 wow fadeInUp" data-wow-delay="0.1s">
                <div class="row g-4">
                    @forelse($products as $product)
                    <div class="col-md-6 col-lg-4">
                        <div class="product-item rounded">
                            <div class="product-item-inner border rounded">
                                <div class="product-item-inner-item">
                                    <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-3.png') }}" 
                                         class="img-fluid w-100 rounded-top" alt="{{ $product->name }}">
                                    @if($product->is_new)
                                        <div class="product-new">New</div>
                                    @endif
                                </div>
                                <div class="text-center p-4">
                                    <a href="{{ route('shop.show', $product->id) }}" class="h4 d-block">{{ $product->name }}</a>
                                    <span class="text-primary fs-5">${{ number_format($product->price, 2) }}</span>
                                </div>
                            </div>
                            <div class="product-item-add border border-top-0 rounded-bottom text-center p-4">
                                <form action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="btn btn-primary rounded-pill py-2 px-4">
                                        <i class="fas fa-shopping-cart me-2"></i> Add To Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center">
                        <p>Không tìm thấy sản phẩm nào.</p>
                    </div>
                    @endforelse

                    <div class="col-12">
    <div class="d-flex justify-content-center mt-5">
        <style>
            /* Ép buộc khối phân trang dàn ngang */
            .pagination {
                display: flex !important;
                flex-direction: row !important;
                padding-left: 0;
                list-style: none;
                gap: 5px;
            }
            .pagination .page-item {
                display: inline-block;
            }
        </style>
        
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection