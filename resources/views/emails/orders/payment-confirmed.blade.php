<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận thanh toán</title>
</head>
<body>
    <h2>Xin chào {{ $order->customer_name }},</h2>
    <p>Chúng tôi đã nhận được thanh toán cho đơn hàng <strong>#{{ $order->tracking_number }}</strong> của bạn tại Electro Shop.</p>
    <p>Trạng thái thanh toán của đơn hàng đã được cập nhật thành: <strong>Đã thanh toán (Thành công)</strong>.</p>
    <p>Chúng tôi sẽ tiến hành đóng gói và giao hàng cho bạn trong thời gian sớm nhất. Bạn có thể theo dõi trạng thái đơn hàng trên website.</p>
    <p>Cảm ơn bạn đã tin tưởng và mua sắm tại Electro Shop.</p>
    <p>Trân trọng,<br>Electro Shop</p>
</body>
</html>
