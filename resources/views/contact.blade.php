@extends('layouts.app')

@section('title', 'Liên hệ - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Liên hệ với chúng tôi</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Liên hệ</li>
        </ol>
    </div>
    <div class="container-fluid contact py-5">
        <div class="container py-5">
            <div class="p-5 bg-light rounded">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 900px;">
                            <h4 class="text-primary border-bottom border-primary border-2 d-inline-block pb-2">Kết nối với chúng tôi</h4>
                            <p class="mb-5 fs-5 text-dark">Chúng tôi luôn sẵn sàng hỗ trợ bạn! Vui lòng để lại thông tin hoặc lời nhắn bên dưới.</p>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <h5 class="text-primary wow fadeInUp" data-wow-delay="0.1s">Gửi tin nhắn</h5>
                        <h1 class="display-5 mb-4 wow fadeInUp" data-wow-delay="0.3s">Bạn cần hỗ trợ gì?</h1>
                        
                        {{-- Form Liên hệ: chỉ hiển thị cho người dùng đã đăng nhập --}}
                        @auth
                            <form action="{{ route('contact.store') }}" method="POST">
                                @csrf
                                <div class="row g-4 wow fadeInUp" data-wow-delay="0.1s">
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Họ và tên" value="{{ old('name', auth()->user()->name) }}" required>
                                            <label for="name">Họ và tên</label>
                                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Email của bạn" value="{{ old('email', auth()->user()->email) }}" required>
                                            <label for="email">Email</label>
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="Số điện thoại" value="{{ old('phone') }}">
                                            <label for="phone">Số điện thoại</label>
                                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" placeholder="Chủ đề" value="{{ old('subject') }}">
                                            <label for="subject">Chủ đề</label>
                                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control @error('message') is-invalid @enderror" placeholder="Nội dung lời nhắn" id="message" name="message" style="height: 160px" required>{{ old('message') }}</textarea>
                                            <label for="message">Nội dung lời nhắn</label>
                                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100 py-3" type="submit">Gửi tin nhắn</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="wow fadeInUp" data-wow-delay="0.1s">
                                <div class="alert alert-warning d-flex align-items-center" role="alert">
                                    <i class="fas fa-lock me-2"></i>
                                    <div>Bạn cần <strong>đăng nhập</strong> để gửi tin nhắn liên hệ cho chúng tôi.</div>
                                </div>
                                <a href="{{ route('login') }}" class="btn btn-primary py-3 px-4 me-2">Đăng nhập ngay</a>
                                <a href="{{ route('register') }}" class="btn btn-outline-primary py-3 px-4">Đăng ký tài khoản</a>
                            </div>
                        @endauth
                    </div>
                    
                    {{-- Google Maps iframe đã được fix lại src --}}
                    <div class="col-lg-5 wow fadeInUp" data-wow-delay="0.2s">
                        <div class="h-100 rounded">
                            <iframe class="rounded w-100" style="height: 100%; min-height: 400px;"
                                src="https://maps.google.com/maps?q=Yên%20Nghĩa,%20Hà%20Đông,%20Hà%20Nội&t=&z=13&ie=UTF8&iwloc=&output=embed"
                                loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                    
                    {{-- Thông tin liên hệ --}}
                    <div class="col-lg-12">
                        <div class="row g-4 align-items-center justify-content-center mt-3">
                            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.1s">
                                <div class="rounded p-4 bg-white text-center shadow-sm h-100">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 70px; height: 70px;">
                                        <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                                    </div>
                                    <h4>Địa chỉ</h4>
                                    <p class="mb-2">Yên Nghĩa, Hà Đông, Hà Nội</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.3s">
                                <div class="rounded p-4 bg-white text-center shadow-sm h-100">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 70px; height: 70px;">
                                        <i class="fas fa-envelope fa-2x text-primary"></i>
                                    </div>
                                    <h4>Email</h4>
                                    <p class="mb-2">hotro@electro.vn</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.5s">
                                <div class="rounded p-4 bg-white text-center shadow-sm h-100">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 70px; height: 70px;">
                                        <i class="fa fa-phone-alt fa-2x text-primary"></i>
                                    </div>
                                    <h4>Hotline</h4>
                                    <p class="mb-2">0123 456 789</p>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.7s">
                                <div class="rounded p-4 bg-white text-center shadow-sm h-100">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4 mx-auto" style="width: 70px; height: 70px;">
                                        <i class="fab fa-firefox-browser fa-2x text-primary"></i>
                                    </div>
                                    <h4>Website</h4>
                                    <p class="mb-2">electro.vn</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('contact_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof showCartToast === 'function') {
                    showCartToast(@json(session('contact_success')), true);
                }
            });
        </script>
    @endif
@endsection