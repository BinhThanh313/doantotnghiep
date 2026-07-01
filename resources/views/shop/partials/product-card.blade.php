{{-- resources/views/shop/partials/product-card.blade.php --}}
<div class="col-md-6 col-lg-3">
    <div class="product-item rounded">
        <div class="product-item-inner border rounded">
            <div class="product-item-inner-item">
                <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('img/product-3.png') }}"
                     class="img-fluid w-100 rounded-top" alt="{{ $product->name }}">
                @if($product->is_new ?? false)
                    <div class="product-new">Mới</div>
                @endif
            </div>
            <div class="text-center p-4">
                <a href="{{ route('shop.show', $product->id) }}" class="h6 d-block">{{ $product->name }}</a>
                <span class="text-primary fs-6">{{ number_format($product->price, 0, ',', '.') }}đ</span>
            </div>
        </div>
        <div class="product-item-add border border-top-0 rounded-bottom text-center p-3">
            <button type="button"
                    class="btn btn-primary btn-sm rounded-pill py-2 px-3 add-to-cart"
                    data-id="{{ $product->id }}">
                <i class="fas fa-shopping-cart me-1"></i> Thêm vào giỏ
            </button>
        </div>
    </div>
</div>