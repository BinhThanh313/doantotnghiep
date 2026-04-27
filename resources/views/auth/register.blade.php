@extends('layouts.app')

@section('title', 'Đăng ký tài khoản - Electro')

@section('content')
{{-- Tiêu đề trang đồng bộ với các trang khác --}}
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Đăng ký tài khoản</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Đăng ký</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0 text-white">Thông tin đăng ký</h4>
                    </div>

                    <div class="card-body p-4 bg-light">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            {{-- Họ và tên --}}
                            <div class="form-item mb-3">
                                <label for="name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                    name="name" value="{{ old('name') }}" required placeholder="Nhập họ và tên của bạn" autofocus>
                                
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="form-item mb-3">
                                <label for="email" class="form-label">Địa chỉ Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                    name="email" value="{{ old('email') }}" required placeholder="example@gmail.com">

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
                                    name="password" required placeholder="Tối thiểu 8 ký tự">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Xác nhận mật khẩu --}}
                            <div class="form-item mb-4">
                                <label for="password-confirm" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input id="password-confirm" type="password" class="form-control" 
                                    name="password_confirmation" required placeholder="Nhập lại mật khẩu">
                            </div>

                            <div class="row mb-0">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary border-secondary w-100 py-3 text-white fw-bold text-uppercase">
                                        Đăng ký ngay
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p class="mb-0">Đã có tài khoản? <a href="{{ route('login') }}" class="text-primary">Đăng nhập ngay</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection