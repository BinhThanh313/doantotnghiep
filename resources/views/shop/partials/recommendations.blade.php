{{-- resources/views/shop/partials/recommendations.blade.php --}}
@if(($recommendations['for_you'] ?? collect())->isNotEmpty())
    <div class="col-12 mt-5">
        <h4 class="fw-bold mb-4">
            <i class="fas fa-user-check text-primary me-2"></i>Gợi ý dành riêng cho bạn
        </h4>
        <div class="row g-4">
            @foreach($recommendations['for_you'] as $p)
                @include('shop.partials.product-card', ['product' => $p])
            @endforeach
        </div>
    </div>
@endif

@if(($recommendations['frequently_bought'] ?? collect())->isNotEmpty())
    <div class="col-12 mt-5">
        <h4 class="fw-bold mb-4">
            <i class="fas fa-people-carry text-primary me-2"></i>Khách hàng cũng mua
        </h4>
        <div class="row g-4">
            @foreach($recommendations['frequently_bought'] as $p)
                @include('shop.partials.product-card', ['product' => $p])
            @endforeach
        </div>
    </div>
@endif

@if(($recommendations['related'] ?? collect())->isNotEmpty())
    <div class="col-12 mt-5">
        <h4 class="fw-bold mb-4">
            <i class="fas fa-layer-group text-primary me-2"></i>Sản phẩm liên quan
        </h4>
        <div class="row g-4">
            @foreach($recommendations['related'] as $p)
                @include('shop.partials.product-card', ['product' => $p])
            @endforeach
        </div>
    </div>
@endif