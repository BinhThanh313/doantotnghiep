@extends('layouts.app')

@section('title', 'Quên mật khẩu - Electro')

@section('content')
{{-- Tiêu đề trang đồng bộ với hệ thống Electro --}}
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Quên mật khẩu</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Lấy lại mật khẩu</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0 text-white">Khôi phục mật khẩu</h4>
                    </div>

                    <div class="card-body p-4 bg-light">
                        {{-- Thông báo khi gửi mail thành công --}}
                        @if (session('status'))
                            <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <p class="text-dark mb-4 text-center">
                            Vui lòng nhập địa chỉ email đã đăng ký. Chúng tôi sẽ gửi một liên kết để bạn có thể đặt lại mật khẩu mới.
                        </p>

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            {{-- Địa chỉ Email --}}
                            <div class="form-item mb-4">
                                <label for="email" class="form-label text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                    name="email" value="{{ old('email') }}" required autocomplete="email" 
                                    placeholder="ví dụ: tenban@gmail.com" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="row mb-0">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary border-secondary w-100 py-3 text-white fw-bold text-uppercase">
                                        Gửi liên kết đặt lại mật khẩu
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="{{ route('login') }}" class="text-secondary small">
                                <i class="fas fa-arrow-left me-2"></i> Quay lại trang đăng nhập
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection