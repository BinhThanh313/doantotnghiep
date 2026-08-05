<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo hoàn tiền</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .refund-amount { background: #fff3cd; border: 2px solid #ffc107; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .refund-amount .amount { font-size: 36px; font-weight: bold; color: #e67e22; }
        .refund-amount p { margin: 5px 0 0; color: #666; font-size: 14px; }
        .info-box { background: #f9f9f9; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .reason-box { background: #fff5f5; border-left: 4px solid #f5576c; padding: 15px 20px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💰 Thông báo hoàn tiền</h1>
        <p>Đơn hàng #{{ $order->tracking_number }}</p>
    </div>

    <div class="body">
        <p>Xin chào <strong>{{ $order->customer_name }}</strong>,</p>
        <p>Chúng tôi xác nhận đã xử lý yêu cầu hoàn tiền cho đơn hàng của bạn.</p>

        <div class="refund-amount">
            <div class="amount">{{ number_format($refundAmount, 0, ',', '.') }}đ</div>
            <p>Số tiền hoàn trả</p>
        </div>

        <div class="reason-box">
            <strong>Lý do hoàn tiền:</strong> {{ $reason }}
        </div>

        <div class="info-box">
            <div class="info-row">
                <span>Mã đơn hàng</span>
                <span><strong>{{ $order->tracking_number }}</strong></span>
            </div>
            <div class="info-row">
                <span>Giá trị đơn hàng</span>
                <span>{{ number_format($order->grand_total, 0, ',', '.') }}đ</span>
            </div>
            <div class="info-row">
                <span>Số tiền hoàn</span>
                <span style="color:#e67e22; font-weight:bold">{{ number_format($refundAmount, 0, ',', '.') }}đ</span>
            </div>
            <div class="info-row">
                <span>Phương thức hoàn tiền</span>
                <span>{{ strtoupper($order->payment_method) }}</span>
            </div>
        </div>

        <p style="font-size:14px; color:#666;">
            ⏱ Thời gian xử lý: <strong>3-5 ngày làm việc</strong> tùy theo ngân hàng/ví điện tử của bạn.<br><br>
            Nếu sau 7 ngày bạn chưa nhận được tiền, vui lòng liên hệ bộ phận hỗ trợ.
        </p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Shop. Hotline: 1900-xxxx | Email: support@shop.vn</p>
    </div>
</div>
</body>
</html>