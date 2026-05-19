@extends('layouts.app')

@section('title', 'Trang cá nhân')

@section('content')
<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Trang cá nhân</h1>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">Thông tin tài khoản</h5>
                </div>
                <div class="card-body">
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
    </div>
</div>
@endsection