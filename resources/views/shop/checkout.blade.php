@extends('layouts.app')

@section('title', 'Thanh toán - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Thanh toán</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="#">Cửa hàng</a></li>
            <li class="breadcrumb-item active text-white">Thanh toán</li>
        </ol>
    </div>

    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-6 col-md-4 col-lg-2 border-start border-end wow fadeInUp" data-wow-delay="0.1s">
                <div class="p-4">
                    <div class="d-inline-flex align-items-center">
                        <i class="fa fa-sync-alt fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Đổi trả miễn phí</h6>
                            <p class="mb-0">Trong vòng 30 ngày!</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.2s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-telegram-plane fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Miễn phí ship</h6>
                            <p class="mb-0">Cho mọi đơn hàng</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.3s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-life-ring fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Hỗ trợ 24/7</h6>
                            <p class="mb-0">Sẵn sàng giải đáp</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.4s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-credit-card fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Quà tặng hấp dẫn</h6>
                            <p class="mb-0">Cho đơn trên 1.000.000đ</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.5s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lock fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Bảo mật thanh toán</h6>
                            <p class="mb-0">An toàn tuyệt đối</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.6s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-blog fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Bảo hành uy tín</h6>
                            <p class="mb-0">Chính hãng 100%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light overflow-hidden py-5">
        <div class="container py-5">
            <h1 class="mb-4 wow fadeInUp" data-wow-delay="0.1s">Chi tiết thanh toán</h1>
            
            <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
                @csrf
                <div class="row g-5">
                    <!-- Bên trái: Thông tin khách hàng -->
                    <div class="col-md-12 col-lg-6 col-xl-6 wow fadeInUp" data-wow-delay="0.1s">
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Họ <sup>*</sup></label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <div class="form-item w-100">
                                    <label class="form-label my-3">Tên <sup>*</sup></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Số điện thoại <sup>*</sup></label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Địa chỉ Email <sup>*</sup></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Tỉnh/Thành phố <sup>*</sup></label>
                            <input type="text" name="province" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Quận/Huyện <sup>*</sup></label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="form-item">
                            <label class="form-label my-3">Địa chỉ cụ thể <sup>*</sup></label>
                            <input type="text" name="address" class="form-control" placeholder="Số nhà, tên đường, phường/xã..." required>
                        </div>
                        <hr>
                        <div class="form-item">
                            <label class="form-label my-3">Ghi chú đơn hàng (Tùy chọn)</label>
                            <textarea name="notes" class="form-control" spellcheck="false" cols="30" rows="6" placeholder="Ghi chú về thời gian giao hàng, địa điểm..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Bên phải -->
                    <div class="col-md-12 col-lg-6 col-xl-6 wow fadeInUp" data-wow-delay="0.3s">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr class="text-center">
                                        <th scope="col" class="text-start">Tên sản phẩm</th>
                                        <th scope="col">Đơn giá</th>
                                        <th scope="col">SL</th>
                                        <th scope="col">Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(session('cart'))
                                        @foreach($cart as $id => $item)
                                        <tr class="text-center">
                                            <th scope="row" class="text-start py-4">{{ $item['name'] }}</th>
                                            <td class="py-4">{{ number_format($item['price'], 0, ',', '.') }}đ</td>
                                            <td class="py-4">{{ $item['quantity'] }}</td>
                                            <td class="py-4 fw-bold">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Voucher -->
                        <div class="mb-4">
                            <label for="checkout-voucher" class="form-label fw-bold">Mã giảm giá</label>
                            <div class="input-group">
                                <input type="text" id="checkout-voucher" 
                                       class="form-control" 
                                       placeholder="Nhập mã voucher"
                                       style="text-transform: uppercase;">
                                <button class="btn btn-outline-primary" type="button" id="apply-voucher-checkout">
                                    Áp dụng
                                </button>
                            </div>
                            <div id="checkout-voucher-message" class="mt-2 small"></div>
                        </div>

                        <!-- Tóm tắt tiền -->
                        <div class="bg-white p-4 rounded border">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tạm tính:</span>
                                <span id="subtotal-display">{{ number_format($subtotal ?? 0, 0, ',', '.') }}đ</span>
                            </div>
                            
                            <div id="discount-row" class="d-flex justify-content-between mb-3 text-success" style="display: none;">
                                <span>Giảm giá (<span id="voucher-name"></span>):</span>
                                <span id="discount-amount">- 0đ</span>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Tổng cộng:</span>
                                <span id="final-total" class="text-primary">{{ number_format($subtotal ?? 0, 0, ',', '.') }}đ</span>
                            </div>
                        </div>

                        <!-- Phí ship -->
                        <div class="row g-0 text-center align-items-center justify-content-center border-bottom py-3 mt-4">
                            <div class="col-12">
                                <div class="form-check text-start my-2">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Shipping-1" name="shipping_fee" value="0" checked>
                                    <label class="form-check-label" for="Shipping-1">Miễn phí vận chuyển (Tiêu chuẩn)</label>
                                </div>
                                <div class="form-check text-start">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Shipping-2" name="shipping_fee" value="30000">
                                    <label class="form-check-label" for="Shipping-2">Giao hàng hỏa tốc: 30.000đ</label>
                                </div>
                            </div>
                        </div>

                        <!-- Phương thức thanh toán -->
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-2">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Payment-COD" name="payment_method" value="cod" checked>
                                    <label class="form-check-label fw-bold" for="Payment-COD">Thanh toán khi nhận hàng (COD)</label>
                                </div>
                                <p class="text-start text-dark small ms-4">Khách hàng thanh toán bằng tiền mặt khi shipper giao hàng tới.</p>
                            </div>
                        </div>
                        <div class="row g-4 text-center align-items-center justify-content-center border-bottom py-3">
                            <div class="col-12">
                                <div class="form-check text-start my-2">
                                    <input type="radio" class="form-check-input bg-primary border-0" id="Payment-Bank" name="payment_method" value="bank">
                                    <label class="form-check-label fw-bold" for="Payment-Bank">Chuyển khoản ngân hàng</label>
                                </div>
                                <p class="text-start text-dark small ms-4">Chuyển khoản trực tiếp qua tài khoản ngân hàng của cửa hàng.</p>
                            </div>
                        </div>

                        <div class="row g-4 text-center align-items-center justify-content-center pt-4">
                            <button type="submit" class="btn btn-primary border-secondary py-3 px-4 text-uppercase w-100 text-white fw-bold">
                                Đặt Hàng Ngay
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkoutForm = document.getElementById('checkout-form');
    const applyBtn       = document.getElementById('apply-voucher-checkout');
    const voucherInput   = document.getElementById('checkout-voucher');
    const messageEl      = document.getElementById('checkout-voucher-message');
    const finalTotalEl   = document.getElementById('final-total');
    const discountRow    = document.getElementById('discount-row');
    const discountAmount = document.getElementById('discount-amount');
    const voucherName    = document.getElementById('voucher-name');

    const currentSubtotal = parseFloat("{{ $subtotal ?? 0 }}") || 0;
    let appliedVoucherCode = null;

    // ==================== VOUCHER ====================
    applyBtn.addEventListener('click', function () {
        const code = voucherInput.value.trim().toUpperCase();

        if (!code) {
            showMessage('Vui lòng nhập mã voucher!', 'danger');
            return;
        }

        applyBtn.disabled = true;
        applyBtn.textContent = 'Đang áp dụng...';

        fetch("{{ route('checkout.apply-voucher') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                amount: currentSubtotal
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const discount = parseFloat(data.discount) || 0;
                const newTotal = currentSubtotal - discount;

                finalTotalEl.textContent = newTotal.toLocaleString('vi-VN') + 'đ';
                discountAmount.textContent = '- ' + discount.toLocaleString('vi-VN') + 'đ';
                voucherName.textContent = code;
                discountRow.style.display = 'flex';
                
                // Lưu mã voucher đã áp dụng
                appliedVoucherCode = code;

                showMessage(data.message, 'success');
                voucherInput.value = '';
            } else {
                showMessage(data.message || 'Mã voucher không hợp lệ', 'danger');
            }
        })
        .catch(() => showMessage('Lỗi kết nối server!', 'danger'))
        .finally(() => {
            applyBtn.disabled = false;
            applyBtn.textContent = 'Áp dụng';
        });
    });

    checkoutForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (appliedVoucherCode) {
            const voucherField = document.createElement('input');
            voucherField.type = 'hidden';
            voucherField.name = 'voucher_code';
            voucherField.value = appliedVoucherCode;
            checkoutForm.appendChild(voucherField);
        }

        const formData = new FormData(checkoutForm);
        
        fetch("{{ route('checkout.store') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Đặt hàng thành công!');
                // ← Sử dụng redirect_url từ response
                window.location.href = data.redirect_url;
            } else {
                alert('❌ Lỗi: ' + (data.message || 'Có lỗi xảy ra'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi kết nối server!');
        });
    });
});
</script>
@endpush