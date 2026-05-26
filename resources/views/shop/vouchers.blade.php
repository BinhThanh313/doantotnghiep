@extends('layouts.app')

@section('title', 'Mã Giảm Giá - Electro')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Mã Giảm Giá</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Mã giảm giá</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row g-4">
            @forelse($vouchers as $voucher)
                <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-primary h-100 shadow-sm">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h5 class="mb-0 text-white">{{ $voucher->name ?? 'Ưu đãi đặc biệt' }}</h5>
                        </div>
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            
                            @if($voucher->discount_type === 'percent')
                                <h2 class="text-primary mb-3">Giảm {{ $voucher->discount_value }}%</h2>
                                @if($voucher->max_discount)
                                    <p class="text-muted small">Tối đa {{ number_format($voucher->max_discount, 0, ',', '.') }}đ</p>
                                @endif
                            @else
                                <h2 class="text-primary mb-3">Giảm {{ number_format($voucher->discount_value, 0, ',', '.') }}đ</h2>
                            @endif

                            <p class="card-text">
                                Đơn hàng tối thiểu: <strong>{{ number_format($voucher->min_amount, 0, ',', '.') }}đ</strong>
                            </p>
                            
                            <div class="border border-dashed border-primary p-2 mb-3 bg-light fs-5 fw-bold text-dark">
                                {{ $voucher->code }}
                            </div>

                            @if($voucher->end_date)
                                <small class="text-danger d-block mb-3">
                                    <i class="far fa-clock"></i> HSD: {{ $voucher->end_date->format('d/m/Y H:i') }}
                                </small>
                            @else
                                <small class="text-success d-block mb-3">Không giới hạn thời gian</small>
                            @endif
                        </div>
                        <div class="card-footer bg-white text-center border-0 pb-4">
                            <button class="btn btn-outline-primary fw-bold w-100 btn-copy" data-code="{{ $voucher->code }}">
                                <i class="fas fa-copy me-2"></i>Sao chép mã
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 wow fadeInUp" data-wow-delay="0.1s">
                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                    <h4>Hiện tại chưa có mã giảm giá nào.</h4>
                    <p>Bạn hãy quay lại sau nhé!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('.btn-copy');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                
                // Copy vào clipboard
                navigator.clipboard.writeText(code).then(() => {
                    // Đổi text nút thành "Đã sao chép" tạm thời
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check text-success me-2"></i>Đã sao chép!';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-outline-success');
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.classList.remove('btn-outline-success');
                        this.classList.add('btn-outline-primary');
                    }, 2000);
                }).catch(err => {
                    console.error('Không thể sao chép: ', err);
                });
            });
        });
    });
</script>
@endsection