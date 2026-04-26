@extends('layouts.app')

@section('title', '404 Không tìm thấy trang - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Lỗi 404</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') ?? '/' }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">404</li>
        </ol>
    </div>
    <div class="container-fluid py-5">
        <div class="container py-5 text-center">
            <div class="row justify-content-center">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                    <i class="bi bi-exclamation-triangle display-1 text-secondary"></i>
                    <h1 class="display-1 fw-bold">404</h1>
                    <h1 class="mb-4">Không tìm thấy trang</h1>
                    <p class="mb-4 text-dark">
                        Rất tiếc, trang bạn đang cố gắng truy cập không tồn tại, đã bị xóa hoặc đã được thay đổi đường dẫn! 
                        Vui lòng quay lại trang chủ hoặc kiểm tra lại đường dẫn.
                    </p>
                    <a class="btn btn-primary rounded-pill py-3 px-5 text-white" href="{{ route('home') ?? '/' }}">
                        Quay lại Trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endsection