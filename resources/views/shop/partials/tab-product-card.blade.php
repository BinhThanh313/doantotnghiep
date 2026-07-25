{{-- resources/views/home/partials/tab-product-card.blade.php --}}
<div class="col-md-6 col-lg-4 col-xl-3">
    <div class="product-item rounded wow fadeInUp">
        <div class="product-item-inner border rounded">
            <div class="product-item-inner-item">
                <img src="{{ img_url($product->image, asset('img/product-3.png')) }}"
                    class="img-fluid w-100 rounded-top" alt="{{ $product->name }}" loading="lazy">
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
                @if($product->is_flash_sale)
                    <div class="mb-1">
                        <span class="badge bg-danger"><i class="fas fa-bolt me-1"></i>Flash Sale -{{ $product->flash_sale_discount_percent }}%</span>
                    </div>
                    <del class="me-2 fs-5 text-muted">{{ number_format($product->price, 0, ',', '.') }}đ</del>
                    <span class="text-danger fs-5 fw-bold">{{ number_format($product->flash_sale_price, 0, ',', '.') }}đ</span>
                @else
                    @if($product->original_price)
                        <del class="me-2 fs-5 text-muted">{{ number_format($product->original_price, 0, ',', '.') }}đ</del>
                    @endif
                    <span class="text-primary fs-5 fw-bold">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                @endif
            </div>
        </div>
        <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
            <button type="button" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4 w-100 add-to-cart" data-id="{{ $product->id }}">
                <i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ
            </button>
            <div class="d-flex justify-content-center">
                <div class="d-flex text-primary">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>
</div>