@extends('layouts.app')

@section('title', 'Trang cá nhân')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Trang cá nhân</h1>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            {{-- ═══════════════ THÔNG BÁO THÀNH CÔNG ═══════════════ --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('success_password'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success_password') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ═══════════════ THÔNG TIN TÀI KHOẢN ═══════════════ --}}
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white"><i class="fas fa-user me-2"></i>Thông tin tài khoản</h5>
                    <button class="btn btn-sm btn-outline-light" type="button" 
                            onclick="document.getElementById('profile-view').classList.toggle('d-none'); document.getElementById('profile-edit').classList.toggle('d-none');">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </button>
                </div>
                <div class="card-body">

                    {{-- ── Chế độ Xem (mặc định) ── --}}
                    <div id="profile-view">
                        <div class="row mb-3">
                            <div class="col-sm-3 fw-bold">Họ và tên:</div>
                            <div class="col-sm-9">{{ Auth::user()->name }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 fw-bold">Email:</div>
                            <div class="col-sm-9">{{ Auth::user()->email }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3 fw-bold">Ngày tham gia:</div>
                            <div class="col-sm-9">{{ Auth::user()->created_at->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    {{-- ── Chế độ Chỉnh sửa (ẩn mặc định) ── --}}
                    <div id="profile-edit" class="d-none">
                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" 
                                       value="{{ old('name', Auth::user()->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" 
                                       value="{{ old('email', Auth::user()->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Ngày tham gia:</div>
                                <div class="col-sm-9">{{ Auth::user()->created_at->format('d/m/Y') }}</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Lưu thay đổi
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="document.getElementById('profile-view').classList.remove('d-none'); document.getElementById('profile-edit').classList.add('d-none');">
                                    Hủy
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ ĐỔI MẬT KHẨU ═══════════════ --}}
            <div class="card shadow mt-4">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center" style="cursor: pointer;"
                     onclick="document.getElementById('password-section').classList.toggle('d-none'); this.querySelector('.toggle-icon').classList.toggle('fa-chevron-down'); this.querySelector('.toggle-icon').classList.toggle('fa-chevron-up');">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>Đổi mật khẩu</h5>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                <div id="password-section" class="card-body d-none">
                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-bold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" name="current_password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Mật khẩu phải có ít nhất 8 ký tự.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-bold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation" required>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-lock me-1"></i>Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>

            {{-- ═══════════════ LỊCH SỬ ĐƠN HÀNG ═══════════════ --}}
            <div class="card shadow mt-4" id="order-history">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white"><i class="fas fa-shopping-bag me-2"></i>Lịch sử đơn hàng</h5>
                </div>
                <div class="card-body p-0">
                    @if($orders->isEmpty())
                        <p class="text-center py-4 text-muted">Bạn chưa có đơn hàng nào.</p>
                    @else
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                <tr>
                                    <td><code>{{ $order->tracking_number }}</code></td>
                                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                    <td class="text-primary fw-bold">
                                        {{ number_format($order->grand_total, 0, ',', '.') }}đ
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill
                                            @if($order->status === 'completed') bg-success
                                            @elseif($order->status === 'cancelled') bg-danger
                                            @elseif(in_array($order->status, ['shipped','delivered'])) bg-info
                                            @else bg-warning text-dark @endif">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('order.detail', $order->id) }}" 
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Xem
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center mt-4" id="pagination">
                            {{ $orders->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
                <a href="{{ route('home') }}" class="btn btn-secondary">Về trang chủ</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #profile-edit .form-control:focus {
        border-color: #F28B00;
        box-shadow: 0 0 0 0.2rem rgba(242, 139, 0, 0.25);
    }
    #password-section .form-control:focus {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }
</style>
@endpush

@if($errors->any())
@push('scripts')
<script>
    // Tự động mở form chỉnh sửa nếu có lỗi validation
    document.addEventListener('DOMContentLoaded', function() {
        @if($errors->has('name') || $errors->has('email'))
            document.getElementById('profile-view').classList.add('d-none');
            document.getElementById('profile-edit').classList.remove('d-none');
        @endif
        @if($errors->has('current_password') || $errors->has('password'))
            document.getElementById('password-section').classList.remove('d-none');
        @endif
    });
</script>
@endpush
@endif
@endsection