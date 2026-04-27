@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu - Electro')

@section('content')
{{-- Tiêu đề trang đồng bộ --}}
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Đặt lại mật khẩu</h1>
    <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item active text-white">Đặt lại mật khẩu</li>
    </ol>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0 text-white">Thiết lập mật khẩu mới</h4>
                    </div>

                    <div class="card-body p-4 bg-light">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            {{-- Địa chỉ Email --}}
                            <div class="form-item mb-3">
                                <label for="email" class="form-label text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                    name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" 
                                    placeholder="Nhập email của bạn" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Mật khẩu mới --}}
                            <div class="form-item mb-3">
                                <label for="password" class="form-label text-dark">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                    name="password" required autocomplete="new-password" placeholder="Nhập mật khẩu mới">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Xác nhận mật khẩu mới --}}
                            <div class="form-item mb-4">
                                <label for="password-confirm" class="form-label text-dark">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <input id="password-confirm" type="password" class="form-control" 
                                    name="password_confirmation" required autocomplete="new-password" 
                                    placeholder="Nhập lại mật khẩu mới">
                            </div>

                            <div class="row mb-0">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary border-secondary w-100 py-3 text-white fw-bold text-uppercase">
                                        Cập nhật mật khẩu
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection