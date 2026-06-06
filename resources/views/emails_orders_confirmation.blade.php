<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #333; padding: 20px; }
        .wrapper { max-width: 620px; margin: 0 auto; }
        .card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }

        /* Header */
        .header { background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%); padding: 36px 30px; text-align: center; }
        .header .logo { font-size: 28px; font-weight: 900; color: #fff; letter-spacing: -1px; margin-bottom: 8px; }
        .header h1 { font-size: 22px; color: #e3f2fd; font-weight: 400; margin-bottom: 6px; }
        .header .tracking { font-size: 18px; font-weight: 700; color: #fff; background: rgba(255,255,255,.15); display: inline-block; padding: 6px 18px; border-radius: 20px; margin-top: 8px; }

        /* Body */
        .body { padding: 32px 30px; }
        .greeting { font-size: 16px; margin-bottom: 20px; color: #555; }
        .greeting strong { color: #1a73e8; }

        /* Summary boxes */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 20px 0; }
        .info-box { background: #f8fafd; border: 1px solid #e0e7ef; border-radius: 8px; padding: 14px 16px; }
        .info-box .label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #888; margin-bottom: 4px; }
        .info-box .value { font-size: 14px; font-weight: 600; color: #222; }

        /* Items table */
        .section-title { font-size: 13px; text-transform: uppercase; letter-spacing: .5px; color: #888; margin: 24px 0 10px; border-bottom: 1px solid #eee; padding-bottom: 6px; }
        .items { width: 100%; border-collapse: collapse; font-size: 14px; }
        .items th { text-align: left; padding: 8px 10px; background: #f8fafd; color: #555; font-weight: 600; }
        .items td { padding: 10px; border-bottom: 1px solid #f0f0f0; }
        .items tr:last-child td { border-bottom: none; }
        .items td:last-child, .items th:last-child { text-align: right; }

        /* Totals */
        .totals { margin-top: 12px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: #555; }
        .total-row.discount { color: #1a9e64; }
        .total-row.grand { font-size: 18px; font-weight: 700; color: #1a73e8; border-top: 2px solid #e0e7ef; padding-top: 12px; margin-top: 6px; }

        /* Payment badge */
        .payment-badge { display: inline-flex; align-items: center; gap: 6px; background: #e8f5e9; color: #2e7d32; border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; margin: 16px 0; }
        .payment-badge.cod   { background: #fff8e1; color: #f57f17; }
        .payment-badge.bank  { background: #e3f2fd; color: #1565c0; }
        .payment-badge.momo  { background: #fce4ec; color: #880e4f; }
        .payment-badge.vnpay { background: #e8eaf6; color: #283593; }

        /* CTA */
        .cta { text-align: center; margin: 28px 0 8px; }
        .cta a { background: #1a73e8; color: #fff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-weight: 700; font-size: 15px; display: inline-block; }

        /* Footer */
        .footer { background: #f8fafd; padding: 20px 30px; text-align: center; font-size: 12px; color: #aaa; border-top: 1px solid #eee; }
        .footer a { color: #1a73e8; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
<div class="card">

    {{-- Header --}}
    <div class="header">
        <div class="logo">⚡ Electro</div>
        <h1>Đơn hàng đã được đặt thành công!</h1>
        <div class="tracking">{{ $order->tracking_number }}</div>
    </div>

    {{-- Body --}}
    <div class="body">
        <p class="greeting">Xin chào <strong>{{ $order->customer_name }}</strong>,</p>
        <p style="color:#555;font-size:14px;margin-bottom:20px;">
            Cảm ơn bạn đã tin tưởng mua sắm tại Electro Shop! Đơn hàng của bạn đã được xác nhận và đang được xử lý.
        </p>

        {{-- Info grid --}}
        <div class="info-grid">
            <div class="info-box">
                <div class="label">Ngày đặt hàng</div>
                <div class="value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-box">
                <div class="label">Trạng thái</div>
                <div class="value">⏳ Chờ xử lý</div>
            </div>
            <div class="info-box">
                <div class="label">Số điện thoại</div>
                <div class="value">{{ $order->customer_phone }}</div>
            </div>
            <div class="info-box">
                <div class="label">Địa chỉ giao hàng</div>
                <div class="value" style="font-size:13px;">{{ $order->address }}, {{ $order->province }}</div>
            </div>
        </div>

        {{-- Payment method badge --}}
        @php $pm = strtolower($order->payment_method ?? 'cod'); @endphp
        <div>
            <span class="payment-badge {{ $pm }}">
                @switch($pm)
                    @case('cod')   💵 Thanh toán khi nhận hàng (COD) @break
                    @case('bank')  🏦 Chuyển khoản ngân hàng @break
                    @case('momo')  🌸 Ví MoMo @break
                    @case('vnpay') 💳 VNPay @break
                    @default {{ strtoupper($pm) }}
                @endswitch
            </span>
        </div>

        {{-- Bank transfer instruction --}}
        @if($pm === 'bank')
        <div style="background:#fffde7;border:1px solid #f9a825;border-radius:8px;padding:16px 20px;margin:16px 0;font-size:14px;">
            <strong>⚠️ Hướng dẫn chuyển khoản:</strong><br><br>
            Ngân hàng: <strong>{{ config('payment.bank.name') }}</strong><br>
            Số tài khoản: <strong>{{ config('payment.bank.account_number') }}</strong><br>
            Tên TK: <strong>{{ config('payment.bank.account_name') }}</strong><br>
            Nội dung CK: <strong>ELECTRO {{ $order->tracking_number }}</strong><br>
            Số tiền: <strong>{{ number_format($grandTotal, 0, ',', '.') }}đ</strong><br><br>
            <span style="color:#e65100">Vui lòng chuyển khoản trong <strong>24 giờ</strong> để đơn hàng không bị hủy.</span>
        </div>
        @endif

        {{-- Items --}}
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
                    <td>{{ $item->product_name }}{{ $item->variant_name ? ' — '.$item->variant_name : '' }}</td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
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
                <span>TỔNG CỘNG</span>
                <span>{{ number_format($grandTotal, 0, ',', '.') }}đ</span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta">
            <a href="{{ url('/checkout/success/' . $order->id) }}">Xem chi tiết đơn hàng →</a>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Electro Shop &nbsp;|&nbsp;
            <a href="{{ url('/') }}">Trang chủ</a> &nbsp;|&nbsp;
            <a href="{{ url('/shop') }}">Cửa hàng</a>
        </p>
        <p style="margin-top:6px;">Hotline: <a href="tel:0123456789">0123 456 789</a> &nbsp;|&nbsp; Email: <a href="mailto:hotro@electro.vn">hotro@electro.vn</a></p>
        <p style="margin-top:8px;color:#ccc;font-size:11px;">Email này được gửi tự động, vui lòng không reply.</p>
    </div>

</div>
</div>
</body>
</html>