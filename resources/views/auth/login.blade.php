@extends('layouts.app')

@section('title', 'Đăng nhập - Electro')

@section('content')
{{-- Tiêu đề trang đồng bộ với hệ thống --}}
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Đăng nhập</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Đăng nhập</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-sm border-0 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0 text-white">Chào mừng trở lại!</h4>
                    </div>

                    <div class="card-body p-4 bg-light">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            {{-- Địa chỉ Email --}}
                            <div class="form-item mb-3">
                                <label for="email" class="form-label">Địa chỉ Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                    name="email" value="{{ old('email') }}" required autocomplete="email" 
                                    placeholder="Nhập email của bạn" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Mật khẩu --}}
                            <div class="form-item mb-3">
                                <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                    name="password" required autocomplete="current-password" 
                                    placeholder="Nhập mật khẩu">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Ghi nhớ & Quên mật khẩu --}}
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        Ghi nhớ đăng nhập
                                    </label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a class="btn btn-link p-0 text-secondary" href="{{ route('password.request') }}">
                                        Quên mật khẩu?
                                    </a>
                                @endif
                            </div>

                            {{-- Nút Đăng nhập --}}
                            <div class="row mb-0">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary border-secondary w-100 py-3 text-white fw-bold text-uppercase">
                                        Đăng nhập
                                    </button>
                                </div>
                            </div>

                            {{-- Link sang Đăng ký --}}
                            <div class="text-center mt-4">
                                <p class="mb-0">Chưa có tài khoản? <a href="{{ route('register') }}" class="text-primary fw-bold">Đăng ký ngay</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection