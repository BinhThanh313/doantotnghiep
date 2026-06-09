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
                            
                                <div class="d-flex justify-content-between mb-2">
                                <span>Phí vận chuyển:</span>
                                <span id="shipping-fee-display">0đ</span>
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
                        <div class="mb-4 mt-4" id="shipping-section">
                            <label class="form-label fw-bold">Đơn vị vận chuyển</label>
                            <p class="text-muted small mb-2">Nhập tỉnh/thành phố bên trên để xem phí ship</p>
                        
                            {{-- Khi chưa có province --}}
                            <div id="carrier-placeholder" class="text-center text-muted py-3 border rounded bg-light">
                                <i class="fas fa-truck me-2"></i>Vui lòng điền tỉnh/thành phố để tải phí ship
                            </div>
                        
                            {{-- Loading spinner --}}
                            <div id="carrier-loading" class="text-center py-3 d-none">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Đang tải phí vận chuyển...
                            </div>
                        
                            {{-- Carrier options (render by JS) --}}
                            <div id="carrier-list" class="d-none"></div>
                        
                            {{-- Hidden inputs --}}
                            <input type="hidden" name="carrier_id" id="selected-carrier-id">
                            <input type="hidden" name="shipping_fee" id="selected-shipping-fee" value="0">
                        </div>

                       <div class="mb-4">
    <h5 class="fw-bold mb-3">Phương thức thanh toán</h5>
    @php
    $paymentMethods = [
        'cod'   => ['label' => 'Thanh toán khi nhận hàng (COD)', 'icon' => '💵', 'desc' => 'Thanh toán bằng tiền mặt khi shipper giao hàng.'],
        'bank'  => ['label' => 'Chuyển khoản ngân hàng', 'icon' => '🏦', 'desc' => 'Chuyển khoản vào tài khoản ngân hàng của cửa hàng.'],
        'vnpay' => ['label' => 'VNPay', 'icon' => '<img src="https://sandbox.vnpayment.vn/paymentv2/Images/logos/vnpay-logo.png" height="22" alt="VNPay">', 'desc' => 'Thanh toán qua cổng VNPay — ATM/Internet Banking/QR.'],
        'momo'  => ['label' => 'Ví MoMo', 'icon' => '<img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" height="22" alt="MoMo">', 'desc' => 'Thanh toán nhanh qua ví điện tử MoMo.'],
    ]
    @endphp

    @foreach($paymentMethods as $value => $pm)
    <div class="row g-4 align-items-center border-bottom py-3">
        <div class="col-12">
            <div class="form-check text-start my-2">
                <input type="radio" class="form-check-input bg-primary border-0" 
                       id="Payment-{{ $value }}" name="payment_method" 
                       value="{{ $value }}" {{ $value === 'cod' ? 'checked' : '' }}>
                <label class="form-check-label fw-bold d-flex align-items-center gap-2" 
                       for="Payment-{{ $value }}">
                    {!! $pm['icon'] !!} {{ $pm['label'] }}
                </label>
            </div>
            <p class="text-start text-dark small ms-4 mb-0">{{ $pm['desc'] }}</p>
        </div>
    </div>
    @endforeach
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
    let currentShippingFee = 0;

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
                const newTotal = currentSubtotal + currentShippingFee - discount;

                finalTotalEl.textContent = newTotal.toLocaleString('vi-VN') + 'đ';
                discountAmount.textContent = '- ' + discount.toLocaleString('vi-VN') + 'đ';
                voucherName.textContent = code;
                discountRow.style.display = 'flex';
                
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

    // ==================== CARRIER DYNAMIC ====================
    let carrierDebounceTimer = null;
    let lastProvince = '';

    const provinceInput   = document.querySelector('input[name="province"]');
    const carrierList     = document.getElementById('carrier-list');
    const carrierLoading  = document.getElementById('carrier-loading');
    const carrierPlaceholder = document.getElementById('carrier-placeholder');
    const carrierIdInput  = document.getElementById('selected-carrier-id');
    const shippingFeeInput= document.getElementById('selected-shipping-fee');

    if (provinceInput) {
        provinceInput.addEventListener('input', function () {
            const province = this.value.trim();
            clearTimeout(carrierDebounceTimer);

            if (province.length < 2) {
                showCarrierPlaceholder();
                return;
            }

            carrierDebounceTimer = setTimeout(() => {
                if (province !== lastProvince) {
                    lastProvince = province;
                    loadCarriers(province);
                }
            }, 600);
        });
    }

    async function loadCarriers(province) {
        showCarrierLoading();
        try {
            const res = await fetch("{{ url('api/shipping/calculate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ province })
            });

            const data = await res.json();

            if (!res.ok || !Array.isArray(data)) {
                throw new Error(data.message || 'Lỗi tải danh sách vận chuyển');
            }

            renderCarrierOptions(data);
        } catch (err) {
            showCarrierError(err.message);
        }
    }

    function renderCarrierOptions(carriers) {
        if (!carriers.length) {
            carrierList.innerHTML = `
                <div class="text-muted small py-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Không có đơn vị vận chuyển nào phục vụ khu vực này.
                </div>`;
            carrierIdInput.value = '';
            shippingFeeInput.value = 0;
            updateShippingDisplay(0);
            showCarrierList();
            return;
        }

        let html = '<div class="list-group">';
        carriers.forEach((c, idx) => {
            const isFirst = idx === 0;
            const feeLabel = c.fee > 0 
                ? new Intl.NumberFormat('vi-VN').format(c.fee) + 'đ' 
                : 'Miễn phí';

            html += `
            <label class="list-group-item list-group-item-action cursor-pointer carrier-option ${isFirst ? 'active border-primary bg-primary bg-opacity-10' : ''}">
                <input type="radio" name="_carrier_radio" value="${c.id}" 
                       data-fee="${c.fee}" class="carrier-radio d-none" ${isFirst ? 'checked' : ''}>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold">${c.name}</span>
                        <small class="text-muted ms-2">${c.estimated_days} ngày</small>
                    </div>
                    <span class="badge ${c.fee > 0 ? 'bg-primary' : 'bg-success'} rounded-pill">${feeLabel}</span>
                </div>
            </label>`;
        });
        html += '</div>';

        carrierList.innerHTML = html;

        if (carriers[0]) {
            setSelectedCarrier(carriers[0].id, carriers[0].fee);
        }

        // Event click
        carrierList.querySelectorAll('.carrier-option').forEach(label => {
            label.addEventListener('click', function () {
                carrierList.querySelectorAll('.carrier-option').forEach(l => 
                    l.classList.remove('active', 'border-primary', 'bg-primary', 'bg-opacity-10')
                );
                this.classList.add('active', 'border-primary', 'bg-primary', 'bg-opacity-10');

                const radio = this.querySelector('.carrier-radio');
                const fee = parseFloat(radio.dataset.fee) || 0;
                setSelectedCarrier(parseInt(radio.value), fee);
            });
        });

        showCarrierList();
    }

    function setSelectedCarrier(carrierId, fee) {
        carrierIdInput.value = carrierId;
        shippingFeeInput.value = fee;
        currentShippingFee = fee;
        updateShippingDisplay(fee);
    }

    function updateShippingDisplay(fee) {
        const feeLabel = fee > 0 
            ? new Intl.NumberFormat('vi-VN').format(fee) + 'đ' 
            : 'Miễn phí';
        document.getElementById('shipping-fee-display').textContent = feeLabel;
        recalcTotal();
    }

    function recalcTotal() {
        const subtotal = currentSubtotal;
        const discountEl = document.getElementById('discount-amount');
        const discount = discountEl 
            ? parseFloat(discountEl.textContent.replace(/[^0-9]/g, '')) || 0 
            : 0;

        const newTotal = subtotal + currentShippingFee - discount;
        finalTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(newTotal) + 'đ';
    }

    function showCarrierPlaceholder() {
        carrierPlaceholder.classList.remove('d-none');
        carrierLoading.classList.add('d-none');
        carrierList.classList.add('d-none');
    }
    function showCarrierLoading() {
        carrierPlaceholder.classList.add('d-none');
        carrierLoading.classList.remove('d-none');
        carrierList.classList.add('d-none');
    }
    function showCarrierList() {
        carrierPlaceholder.classList.add('d-none');
        carrierLoading.classList.add('d-none');
        carrierList.classList.remove('d-none');
    }
    function showCarrierError(msg) {
        carrierList.innerHTML = `<div class="alert alert-warning small py-2 mb-0">
            <i class="fas fa-exclamation-triangle me-1"></i>${msg || 'Không tải được phí ship.'}
        </div>`;
        showCarrierList();
    }

    function showMessage(msg, type) {
        messageEl.innerHTML = `<span class="text-${type}">${msg}</span>`;
        setTimeout(() => messageEl.innerHTML = '', 5000);
    }

    // Submit form
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
        
        // Disable nút submit để tránh click nhiều lần
        const submitBtn = checkoutForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Đang xử lý...';

        fetch("{{ route('checkout.store') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json' // Bắt buộc để nhận JSON errors từ Laravel
            },
            body: formData
        })
        .then(async r => {
            const data = await r.json();
            
            // Nếu request không thành công (vd: lỗi 422, 500)
            if (!r.ok) {
                return Promise.reject(data);
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                alert('✅ Đặt hàng thành công!');
                window.location.href = data.redirect_url;
            } else {
                alert('❌ Lỗi: ' + (data.message || 'Có lỗi xảy ra'));
            }
        })
        .catch(err => {
            let errorString = '❌ Có lỗi xảy ra trong quá trình đặt hàng:\n\n';
            
            if (err && err.errors) {
                // Trường hợp errors là một mảng (Array)
                if (Array.isArray(err.errors)) {
                    err.errors.forEach(msg => {
                        errorString += `- ${msg}\n`;
                    });
                } 
                // Trường hợp errors là một đối tượng (Object - Mặc định của Laravel)
                else if (typeof err.errors === 'object') {
                    for (let field in err.errors) {
                        if (Array.isArray(err.errors[field])) {
                            errorString += `- ${err.errors[field][0]}\n`;
                        } else {
                            errorString += `- ${err.errors[field]}\n`;
                        }
                    }
                } else {
                    errorString += `- ${err.errors}\n`;
                }
            } 
            else if (err && err.message) {
                errorString += err.message;
            } 
            else {
                errorString += 'Vui lòng kiểm tra lại thông tin trên form.';
            }
            
            alert(errorString);
        })
        .finally(() => {
            // Trả lại trạng thái cho nút submit
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
    });
</script>
@endpush