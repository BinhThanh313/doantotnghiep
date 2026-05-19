@extends('layouts.app')

@section('title', 'Cửa hàng - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Cửa hàng</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Cửa hàng</li>
    </ol>
</div>

<div class="container-fluid shop py-5">
    <div class="container py-5">
        <div class="row g-4">
            
            <!-- Sidebar Danh mục -->
            <div class="col-lg-3 wow fadeInUp" data-wow-delay="0.1s">
                <div class="product-categories mb-4">
                    <h4>Danh mục sản phẩm</h4>
                    <ul class="list-unstyled">
                        <!-- Tất cả sản phẩm -->
                        <li>
                            <div class="categories-item">
                                <a href="{{ route('shop.index') }}" 
                                   class="category-link text-dark {{ !request('category') ? 'fw-bold' : '' }}" 
                                   data-category="">
                                    <i class="fas fa-apple-alt text-secondary me-2"></i>Tất cả sản phẩm
                                </a>
                            </div>
                        </li>
                        
                        @foreach($categories as $cat)
                        <li>
                            <div class="categories-item">
                                <a href="{{ route('shop.index', ['category' => $cat->id]) }}" 
                                   class="category-link text-dark {{ request('category') == $cat->id ? 'fw-bold' : '' }}" 
                                   data-category="{{ $cat->id }}">
                                    <i class="fas fa-apple-alt text-secondary me-2"></i>{{ $cat->name }}
                                </a>
                                <span>({{ $cat->products_count }})</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Phần sản phẩm + Phân trang (AJAX) -->
            <div class="col-lg-9 wow fadeInUp" data-wow-delay="0.1s">
                <div id="products-wrapper">
                    @include('shop.products')
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    /* Phân trang */
    #pagination .pagination {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        justify-content: center;
        gap: 6px;
        margin-bottom: 0;
    }
    
    #pagination .page-item { display: inline-block !important; }

    /* Ẩn "Showing ..." */
    #pagination .pagination li:first-child:not(.page-item),
    #pagination .pagination li:contains("Showing"),
    #pagination .text-muted {
        display: none !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const wrapper = document.getElementById('products-wrapper');

    // ==================== DANH MỤC & PHÂN TRANG ====================
    document.querySelectorAll('.category-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const categoryId = this.getAttribute('data-category');
            loadProducts({ category: categoryId, page: 1 });
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('#pagination a')) {
            e.preventDefault();
            const url = new URL(e.target.closest('a').href);
            const page = url.searchParams.get('page') || 1;
            loadProducts({ page: page });
        }
    });

    // ==================== THÊM VÀO GIỎ HÀNG (AJAX) ====================
    document.addEventListener('click', function (e) {
        if (e.target.closest('.add-to-cart')) {
            const btn = e.target.closest('.add-to-cart');
            const productId = btn.getAttribute('data-id');
            
            // Disable button tạm thời
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Đang thêm...`;

            fetch("{{ route('cart.add') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Đã thêm sản phẩm vào giỏ hàng!');
                    // Có thể cập nhật số lượng giỏ hàng ở header nếu bạn có
                    if (typeof updateCartCount === 'function') updateCartCount(data.cart_count);
                } else {
                    alert(data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error(error);
                alert('Có lỗi kết nối. Vui lòng thử lại!');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-shopping-cart me-2"></i> Thêm vào giỏ hàng`;
            });
        }
    });

    // Hàm load sản phẩm
    function loadProducts(params = {}) {
        let url = "{{ route('shop.index') }}";
        let query = new URLSearchParams();

        if (params.category !== undefined) query.append('category', params.category);
        if (params.page) query.append('page', params.page);

        const fullUrl = url + (query.toString() ? '?' + query.toString() : '');

        history.pushState({}, '', fullUrl);

        fetch(fullUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            wrapper.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
        });
    }
});
</script>
@endpush