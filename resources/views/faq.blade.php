@extends('layouts.app')

@section('title', 'Câu hỏi thường gặp - Electro')

@section('content')
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Câu hỏi thường gặp</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active text-white">Câu hỏi thường gặp</li>
        </ol>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion" id="faqAccordion">
                        @php
                            $faqs = [
                                [
                                    'q' => 'Làm thế nào để đặt hàng trên Electro Shop?',
                                    'a' => 'Bạn chọn sản phẩm, bấm "Thêm vào giỏ hàng", sau đó vào Giỏ hàng và bấm "Thanh toán". Điền thông tin giao hàng và chọn phương thức thanh toán để hoàn tất đơn hàng.',
                                ],
                                [
                                    'q' => 'Electro Shop hỗ trợ những phương thức thanh toán nào?',
                                    'a' => 'Chúng tôi hỗ trợ thanh toán khi nhận hàng (COD) và chuyển khoản ngân hàng qua VietQR.',
                                ],
                                [
                                    'q' => 'Tôi có thể theo dõi đơn hàng ở đâu?',
                                    'a' => 'Bạn có thể xem trạng thái và lịch sử đơn hàng tại trang Tài khoản của tôi, mục "Lịch sử đơn hàng".',
                                ],
                                [
                                    'q' => 'Chính sách đổi trả sản phẩm như thế nào?',
                                    'a' => 'Vui lòng tham khảo chi tiết tại trang Chính sách đổi trả ở chân trang web.',
                                ],
                                [
                                    'q' => 'Làm sao để sử dụng mã giảm giá (voucher)?',
                                    'a' => 'Ở bước thanh toán, bạn nhập mã voucher vào ô "Mã giảm giá" và bấm "Áp dụng" để hệ thống tự động trừ vào tổng giá trị đơn hàng.',
                                ],
                                [
                                    'q' => 'Tôi cần hỗ trợ thêm thì liên hệ bằng cách nào?',
                                    'a' => 'Bạn có thể gửi yêu cầu qua trang Liên hệ, hoặc trò chuyện trực tiếp với trợ lý ảo trên website.',
                                ],
                            ];
                        @endphp

                        @foreach($faqs as $index => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}"
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                        {{ $faq['q'] }}
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                    aria-labelledby="heading{{ $index }}" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        {{ $faq['a'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
