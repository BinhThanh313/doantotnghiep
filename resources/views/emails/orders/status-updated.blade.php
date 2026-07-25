<x-mail::message>
# Cập nhật đơn hàng #{{ $order->tracking_number }}

Xin chào {{ $order->customer_name }},

Trạng thái đơn hàng của bạn đã được cập nhật thành: **{{ $statusMessage }}**

@if($newStatus === 'shipped')
Đơn vị vận chuyển: {{ $order->shipment->carrier->name ?? 'Đang cập nhật' }}
Mã vận đơn: {{ $order->shipment->tracking_number ?? 'Đang cập nhật' }}
@endif

Tổng giá trị đơn hàng: {{ number_format($grandTotal, 0, ',', '.') }}đ

<x-mail::button :url="url('/order/' . $order->id)">
Xem Chi Tiết Đơn Hàng
</x-mail::button>

Cảm ơn bạn đã mua sắm tại cửa hàng của chúng tôi!
</x-mail::message>
