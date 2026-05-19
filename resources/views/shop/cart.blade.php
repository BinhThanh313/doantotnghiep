@extends('layouts.app')

@section('title', 'Giỏ hàng - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Giỏ hàng của bạn</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Cửa hàng</a></li>
        <li class="breadcrumb-item active text-white">Giỏ hàng</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="table-responsive">
            <table class="table" id="cart-table">
                <thead>
                    <tr>
                        <th scope="col">Sản phẩm</th>
                        <th scope="col">Mã SP</th>
                        <th scope="col">Đơn giá</th>
                        <th scope="col">Số lượng</th>
                        <th scope="col">Thành tiền</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    @include('shop.cart-items')
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            <input type="text" class="border-0 border-bottom rounded me-5 py-3 mb-4" placeholder="Mã giảm giá">
            <button class="btn btn-primary rounded-pill px-4 py-3 text-white" type="button">Áp dụng</button>
        </div>

        <div class="row g-4 justify-content-end">
            <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                <div class="bg-light rounded" id="cart-summary">
                    @include('shop.cart-summary')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.addEventListener('click', function (e) {
        // Tăng / Giảm
        if (e.target.closest('.quantity-btn')) {
            const btn = e.target.closest('.quantity-btn');
            const id = btn.dataset.id;
            const action = btn.dataset.action;
            updateQuantity(id, action);
        }

        // Xóa
        if (e.target.closest('.remove-item')) {
            const btn = e.target.closest('.remove-item');
            if (confirm('Xóa sản phẩm này khỏi giỏ hàng?')) {
                removeItem(btn.dataset.id);
            }
        }
    });

    function updateQuantity(id, action) {
        fetch("{{ url('cart/update') }}/" + id, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: action })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadCart();
        })
        .catch(() => alert('Lỗi kết nối'));
    }

    function removeItem(id) {
        fetch("{{ url('cart/remove') }}/" + id, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadCart();
        });
    }

    function loadCart() {
        fetch("{{ route('cart.index') }}?ajax=1", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            document.getElementById('cart-body').innerHTML = temp.querySelector('#cart-body')?.innerHTML || '';
            document.getElementById('cart-summary').innerHTML = temp.querySelector('#cart-summary')?.innerHTML || '';
        });
    }
});
</script>
@endpush