<div class="row g-4" id="product-list">
    @forelse($products as $product)
    <div class="col-md-6 col-lg-4">
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
                    <a href="{{ route('shop.show', $product->id) }}" class="h4 d-block">{{ $product->name }}</a>
                    @if($product->is_flash_sale)
                        <div class="mb-1">
                            <span class="badge bg-danger"><i class="fas fa-bolt me-1"></i>-{{ $product->flash_sale_discount_percent }}%</span>
                        </div>
                        <del class="text-muted me-2">{{ number_format($product->price, 0, ',', '.') }}đ</del>
                        <span class="text-danger fs-5 fw-bold">{{ number_format($product->flash_sale_price, 0, ',', '.') }}đ</span>
                    @else
                        <span class="text-primary fs-5">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                    @endif
                </div>
            </div>
            <!-- Trong vòng lặp sản phẩm -->
<div class="product-item-add border border-top-0 rounded-bottom text-center p-4">
    <button type="button" 
            class="btn btn-primary rounded-pill py-2 px-4 add-to-cart"
            data-id="{{ $product->id }}">
        <i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ hàng
    </button>
</div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <p>Không tìm thấy sản phẩm nào.</p>
    </div>
    @endforelse
</div>

<!-- Phân trang -->
<div class="col-12 mt-5">
    <div class="d-flex justify-content-center" id="pagination">
        {{ $products->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
    </div>
</div>

