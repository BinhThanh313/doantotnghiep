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
                        <th scope="col">
                            <input type="checkbox" id="select-all-items" class="form-check-input"
                                   style="width: 1.2em; height: 1.2em;" checked
                                   title="Chọn / bỏ chọn tất cả">
                        </th>
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

        <!-- Voucher Section -->
        <div class="mt-5">
            <div class="input-group" style="max-width: 420px;">
                <input type="text" id="voucher-code"
                       class="form-control border-0 border-bottom rounded-start py-3"
                       placeholder="Nhập mã giảm giá" style="text-transform: uppercase;">
                <button class="btn btn-primary rounded-end px-5" type="button" id="apply-voucher-btn">
                    Áp dụng
                </button>
            </div>
            <div id="voucher-message" class="mt-2 fs-6"></div>
        </div>

        <div class="row g-4 justify-content-end">
            <div class="col-sm-8 col-md-7 col-lg-6 col-xl-4">
                <div class="bg-light rounded" id="cart-summary">
                    @include('shop.cart-summary')
                </div>
            </div>
        </div>

        {{-- Hidden input để truyền tổng tiền --}}
        <input type="hidden" id="cart-total-hidden" value="{{ $total ?? 0 }}">
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ====================== QUANTITY & REMOVE ======================
    document.addEventListener('click', function (e) {
        if (e.target.closest('.quantity-btn')) {
            const btn = e.target.closest('.quantity-btn');
            const id = btn.dataset.id;
            const action = btn.dataset.action;
            updateQuantity(id, action);
        }

        if (e.target.closest('.remove-item')) {
            const btn = e.target.closest('.remove-item');
            if (confirm('Xóa sản phẩm này khỏi giỏ hàng?')) {
                removeItem(btn.dataset.id);
            }
        }
    });

    let debounceTimer = null;

    function updateQuantity(id, action) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            // Disable nút trong lúc đang gửi request
            document.querySelectorAll('.quantity-btn[data-id="' + id + '"]')
                    .forEach(btn => btn.disabled = true);

            fetch('{{ url("cart/update") }}/' + id, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) loadCart();
            })
            .catch(() => alert('Lỗi kết nối'))
            .finally(() => {
                document.querySelectorAll('.quantity-btn[data-id="' + id + '"]')
                        .forEach(btn => btn.disabled = false);
            });
        }, 500);
    }

    function removeItem(id) {
        fetch('{{ url("cart/remove") }}/' + id, {
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
        fetch('{{ route("cart.index") }}?ajax=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('cart-body').innerHTML = data.cart_html;
            document.getElementById('cart-summary').innerHTML = data.summary_html;
            const selectAll = document.getElementById('select-all-items');
            if (selectAll) selectAll.checked = true;
            updateSelectedTotal();
        });
    }

    // ====================== CHỌN SẢN PHẨM ĐỂ THANH TOÁN ======================
    function updateSelectedTotal() {
        let selectedTotal = 0;
        document.querySelectorAll('.item-select:checked').forEach(cb => {
            selectedTotal += parseFloat(cb.dataset.subtotal) || 0;
        });

        // Cập nhật tạm tính
        const summaryEl = document.getElementById('cart-summary');
        if (summaryEl) {
            const subtotalEl = summaryEl.querySelector('.d-flex.justify-content-between.mb-3 p');
            if (subtotalEl) {
                subtotalEl.textContent = new Intl.NumberFormat('vi-VN').format(selectedTotal) + 'đ';
            }
            // Cập nhật tổng cộng
            const totalEl = summaryEl.querySelector('.d-flex.justify-content-between.border-top.pt-3 p');
            if (totalEl) {
                totalEl.textContent = new Intl.NumberFormat('vi-VN').format(selectedTotal) + 'đ';
            }
        }

        // Cập nhật hidden input
        const hiddenInput = document.getElementById('cart-total-hidden');
        if (hiddenInput) hiddenInput.value = selectedTotal;
    }

    document.addEventListener('change', function (e) {
        if (e.target.id === 'select-all-items') {
            document.querySelectorAll('.item-select').forEach(cb => cb.checked = e.target.checked);
            updateSelectedTotal();
        }
        if (e.target.classList.contains('item-select')) {
            const allBoxes = document.querySelectorAll('.item-select');
            const selectAll = document.getElementById('select-all-items');
            if (selectAll) {
                selectAll.checked = allBoxes.length > 0 && Array.from(allBoxes).every(cb => cb.checked);
            }
            updateSelectedTotal();
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('#checkout-btn')) {
            const selectedIds = Array.from(document.querySelectorAll('.item-select:checked'))
                .map(cb => cb.dataset.id);

            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán!');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("checkout.select-items") }}';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(tokenInput);

            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'item_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    });

    // ====================== VOUCHER ======================
    const applyBtn    = document.getElementById('apply-voucher-btn');
    const voucherInput = document.getElementById('voucher-code');
    const messageEl   = document.getElementById('voucher-message');
    const cartTotal   = parseFloat(document.getElementById('cart-total-hidden')?.value) || 0;

    applyBtn.addEventListener('click', function () {
        const code = voucherInput.value.trim().toUpperCase();

        if (!code) {
            showMessage('Vui lòng nhập mã voucher!', 'danger');
            return;
        }

        applyBtn.disabled = true;
        applyBtn.innerHTML = 'Đang áp dụng...';

        fetch('{{ route("checkout.apply-voucher") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ code, amount: cartTotal })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                loadCart();
                voucherInput.value = '';
            } else {
                showMessage(data.message, 'danger');
            }
        })
        .catch(() => showMessage('Lỗi kết nối server!', 'danger'))
        .finally(() => {
            applyBtn.disabled = false;
            applyBtn.innerHTML = 'Áp dụng';
        });
    });

    function showMessage(message, type) {
        const clearBtn = type === 'success'
            ? ' <button id="clear-voucher-btn" class="btn btn-sm btn-outline-danger ms-2">✕ Xóa</button>'
            : '';
        messageEl.innerHTML = `<span class="text-${type}">${message}</span>${clearBtn}`;
    }

    // ====================== CLEAR VOUCHER ======================
    document.addEventListener('click', function (e) {
        if (e.target.id === 'clear-voucher-btn') {
            fetch('{{ url("cart/clear-voucher") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(r => r.json())
            .then(() => {
                loadCart();
                messageEl.innerHTML = '';
            });
        }
    });

});
</script>
@endpush