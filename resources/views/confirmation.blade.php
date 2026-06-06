<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #f0f2f5; color: #333; padding: 20px; }
        .wrapper { max-width: 620px; margin: 0 auto; }
        .card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.09); margin-bottom: 20px; }
        .header { background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%); padding: 36px 30px; text-align: center; color: #fff; }
        .header .icon { font-size: 48px; display: block; margin-bottom: 12px; }
        .header h1 { font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .header p { font-size: 14px; opacity: .85; margin-top: 6px; }
        .tracking { display: inline-block; background: rgba(255,255,255,.2); border: 1.5px solid rgba(255,255,255,.4); color: #fff; padding: 6px 18px; border-radius: 20px; font-weight: 700; font-size: 15px; margin-top: 14px; letter-spacing: 1px; }
        .section { padding: 24px 30px; }
        .section + .section { border-top: 1px solid #f0f0f0; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 14px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .info-item label { font-size: 12px; color: #888; display: block; margin-bottom: 4px; }
        .info-item span { font-size: 14px; color: #222; font-weight: 500; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: .5px; padding: 8px 0; border-bottom: 2px solid #f0f0f0; text-align: left; }
        table.items th:last-child, table.items td:last-child { text-align: right; }
        table.items td { padding: 12px 0; font-size: 14px; border-bottom: 1px solid #f8f8f8; }
        table.items td.name { font-weight: 500; }
        table.items td.qty { color: #888; font-size: 13px; text-align: center; }
        .totals { background: #f9fafb; border-radius: 10px; padding: 16px 20px; margin-top: 16px; }
        .total-row { display: flex; justify-content: space-between; font-size: 14px; padding: 5px 0; }
        .total-row.grand { font-size: 17px; font-weight: 700; color: #1a73e8; border-top: 2px solid #e8eaed; margin-top: 8px; padding-top: 12px; }
        .total-row.discount { color: #0a8a4a; }
        /* Bank Transfer */
        .bank-box { background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border: 1.5px dashed #4caf50; border-radius: 12px; padding: 20px 24px; margin-top: 12px; }
        .bank-box h3 { color: #2e7d32; font-size: 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .bank-detail { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px solid rgba(76,175,80,.15); }
        .bank-detail:last-child { border: none; }
        .bank-detail .label { color: #555; }
        .bank-detail .value { font-weight: 600; color: #1b5e20; }
        .transfer-note-box { margin-top: 14px; background: #fff9c4; border: 1.5px solid #f9a825; border-radius: 8px; padding: 12px 16px; font-size: 13px; }
        .transfer-note-box .note-label { font-weight: 700; color: #f57f17; display: block; margin-bottom: 4px; }
        .transfer-note-box .note-value { font-size: 18px; font-weight: 700; color: #e65100; letter-spacing: 1px; }
        .qr-section { text-align: center; margin-top: 16px; }
        .qr-section img { width: 180px; height: 180px; border-radius: 12px; border: 2px solid #e0e0e0; }
        .qr-caption { font-size: 12px; color: #888; margin-top: 8px; }
        .deadline-badge { display: inline-block; background: #fff3e0; color: #e65100; border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 600; margin-top: 12px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-cod { background: #e3f2fd; color: #1565c0; }
        .status-bank { background: #e8f5e9; color: #2e7d32; }
        .footer { text-align: center; font-size: 12px; color: #aaa; padding: 20px; }
        .footer a { color: #1a73e8; text-decoration: none; }
        @media(max-width:480px) {
            .info-grid { grid-template-columns: 1fr; }
            .section { padding: 18px 18px; }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- Header -->
    <div class="card">
        <div class="header">
            <span class="icon">🎉</span>
            <h1>Đặt hàng thành công!</h1>
            <p>Cảm ơn bạn đã tin tưởng Electro Shop</p>
            <div class="tracking">{{ $order->tracking_number }}</div>
        </div>

        <!-- Thông tin khách hàng -->
        <div class="section">
            <div class="section-title">Thông tin giao hàng</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Khách hàng</label>
                    <span>{{ $order->customer_name }}</span>
                </div>
                <div class="info-item">
                    <label>Điện thoại</label>
                    <span>{{ $order->customer_phone }}</span>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <span>{{ $order->customer_email }}</span>
                </div>
                <div class="info-item">
                    <label>Ngày đặt</label>
                    <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="info-item" style="grid-column: 1/-1;">
                    <label>Địa chỉ giao hàng</label>
                    <span>{{ $order->address }}, {{ $order->province }}</span>
                </div>
            </div>
        </div>

        <!-- Sản phẩm -->
        <div class="section">
            <div class="section-title">Sản phẩm đã đặt</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align:center">SL</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td class="name">{{ $item->product_name }}</td>
                        <td class="qty">{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="total-row">
                    <span>Tạm tính</span>
                    <span>{{ number_format($order->total_amount, 0, ',', '.') }}đ</span>
                </div>
                @if(($order->shipping_fee ?? 0) > 0)
                <div class="total-row">
                    <span>Phí vận chuyển</span>
                    <span>{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</span>
                </div>
                @endif
                @if(($order->discount_amount ?? 0) > 0)
                <div class="total-row discount">
                    <span>Giảm giá</span>
                    <span>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span>
                </div>
                @endif
                <div class="total-row grand">
                    <span>Tổng thanh toán</span>
                    <span>{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </div>

        <!-- Thanh toán -->
        <div class="section">
            <div class="section-title">Phương thức thanh toán</div>

            @if($isBankTransfer)
            <span class="status-badge status-bank">🏦 Chuyển khoản ngân hàng</span>

            <div class="bank-box" style="margin-top:14px">
                <h3>🏦 Thông tin chuyển khoản</h3>
                <div class="bank-detail">
                    <span class="label">Ngân hàng</span>
                    <span class="value">{{ $bankInfo['bank_name'] }}</span>
                </div>
                <div class="bank-detail">
                    <span class="label">Số tài khoản</span>
                    <span class="value">{{ $bankInfo['account_number'] }}</span>
                </div>
                <div class="bank-detail">
                    <span class="label">Chủ tài khoản</span>
                    <span class="value">{{ $bankInfo['account_name'] }}</span>
                </div>
                <div class="bank-detail">
                    <span class="label">Số tiền</span>
                    <span class="value">{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
                </div>

                <div class="transfer-note-box">
                    <span class="note-label">⚠️ Nội dung chuyển khoản (BẮT BUỘC)</span>
                    <div class="note-value">{{ $bankInfo['transfer_note'] }}</div>
                </div>

                @if(!empty($bankInfo['qr_url']))
                <div class="qr-section">
                    <img src="{{ $bankInfo['qr_url'] }}" alt="QR chuyển khoản">
                    <div class="qr-caption">Quét mã QR để chuyển khoản nhanh</div>
                </div>
                @endif

                <div style="text-align:center">
                    <span class="deadline-badge">⏰ Vui lòng thanh toán trong 24 giờ</span>
                </div>
            </div>
            @else
            <span class="status-badge status-cod">💵 Thanh toán khi nhận hàng (COD)</span>
            <p style="margin-top:10px; font-size:13px; color:#666;">
                Bạn sẽ thanh toán bằng tiền mặt khi shipper giao hàng đến địa chỉ của bạn.
            </p>
            @endif
        </div>

        @if($order->notes)
        <div class="section">
            <div class="section-title">Ghi chú đơn hàng</div>
            <p style="font-size:14px; color:#555;">{{ $order->notes }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Electro Shop | Hotline: 0123 456 789 | <a href="mailto:hotro@electro.vn">hotro@electro.vn</a></p>
        <p style="margin-top:6px;">Email này được gửi tự động, vui lòng không reply trực tiếp.</p>
    </div>
</div>
</body>
</html>