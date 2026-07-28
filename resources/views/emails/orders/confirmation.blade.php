<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận đơn hàng</title>
</head>
<body>
    <h2>Xin chào {{ $order->customer_name }},</h2>
    <p>Cảm ơn bạn đã đặt hàng tại Electro. Đơn hàng <strong>#{{ $order->tracking_number }}</strong> của bạn đã được tạo thành công.</p>
    
    <p><strong>Tổng tiền:</strong> {{ number_format($grandTotal, 0, ',', '.') }}đ</p>
    <p><strong>Phương thức thanh toán:</strong> {{ strtoupper($order->payment_method) }}</p>

    @if($isBankTransfer && !empty($bankInfo))
        <hr>
        <h3>Hướng dẫn thanh toán chuyển khoản:</h3>
        <p>Ngân hàng: {{ $bankInfo['bank_name'] }}</p>
        <p>Số tài khoản: {{ $bankInfo['account_no'] }}</p>
        <p>Chủ tài khoản: {{ $bankInfo['account_name'] }}</p>
        <p>Nội dung chuyển khoản: <strong>{{ $bankInfo['content'] }}</strong></p>
        <p>Số tiền: <strong>{{ number_format($bankInfo['amount'], 0, ',', '.') }}đ</strong></p>
    @endif

    <p>Chúng tôi sẽ liên hệ và giao hàng cho bạn trong thời gian sớm nhất.</p>
    <p>Trân trọng,<br>Electro Shop</p>
</body>
</html>
