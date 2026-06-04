<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật đơn hàng</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 8px 0 0; opacity: 0.85; font-size: 14px; }
        .status-badge { display: inline-block; background: rgba(255,255,255,0.25); border: 2px solid rgba(255,255,255,0.5); color: #fff; padding: 8px 20px; border-radius: 20px; font-weight: bold; margin-top: 12px; font-size: 16px; }
        .body { padding: 30px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .message-box { background: #f0f4ff; border-left: 4px solid #667eea; padding: 15px 20px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 15px; }
        .order-info { background: #f9f9f9; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .order-info h3 { margin: 0 0 15px; color: #555; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .info-row:last-child { border-bottom: none; font-weight: bold; font-size: 15px; color: #667eea; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        .items-table th { background: #667eea; color: #fff; padding: 10px 12px; text-align: left; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .items-table tr:last-child td { border-bottom: none; }
        .footer { background: #f5f5f5; padding: 20px 30px; text-align: center; font-size: 12px; color: #999; }
        .btn { display: inline-block; background: #667eea; color: #fff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🛍️ Cập nhật đơn hàng</h1>
        <p>Đơn hàng #{{ $order->tracking_number }}</p>
        <div class="status-badge">{{ $order->status_label }}</div>
    </div>

    <div class="body">
        <p class="greeting">Xin chào <strong>{{ $order->customer_name }}</strong>,</p>

        <div class="message-box">
            {{ $statusMessage }}
        </div>

        <div class="order-info">
            <h3>Thông tin đơn hàng</h3>
            <div class="info-row">
                <span>Mã đơn hàng</span>
                <span><strong>{{ $order->tracking_number }}</strong></span>
            </div>
            <div class="info-row">
                <span>Ngày đặt hàng</span>
                <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span>Địa chỉ giao hàng</span>
                <span>{{ $order->address }}, {{ $order->province }}</span>
            </div>
            <div class="info-row">
                <span>Phương thức thanh toán</span>
                <span>{{ strtoupper($order->payment_method) }}</span>
            </div>
        </div>

        @if($order->items && $order->items->count() > 0)
        <table class="items-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th style="text-align:center">SL</th>
                    <th style="text-align:right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:right">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="order-info">
            <h3>Tổng tiền</h3>
            <div class="info-row">
                <span>Tạm tính</span>
                <span>{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
            </div>
            <div class="info-row">
                <span>Phí vận chuyển</span>
                <span>{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }}đ</span>
            </div>
            @if(($order->discount_amount ?? 0) > 0)
            <div class="info-row" style="color:#10b981">
                <span>Giảm giá</span>
                <span>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
            </div>
            @endif
            <div class="info-row">
                <span>TỔNG CỘNG</span>
                <span>{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
            </div>
        </div>

        @if($newStatus === 'cancelled')
        <p style="color:#ef4444; font-size:14px">
            Nếu bạn đã thanh toán, chúng tôi sẽ liên hệ để hoàn tiền trong vòng 3-5 ngày làm việc.
        </p>
        @endif

        <p style="font-size:14px; color:#666; margin-top:20px;">
            Nếu có thắc mắc, vui lòng liên hệ hotline hoặc email hỗ trợ của chúng tôi.<br>
            Xin cảm ơn bạn đã tin tưởng mua sắm!
        </p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Shop. Mọi thắc mắc xin liên hệ support@shop.vn</p>
        <p>Email này được gửi tự động, vui lòng không reply.</p>
    </div>
</div>
</body>
</html>