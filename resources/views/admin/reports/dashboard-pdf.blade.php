<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 28px 30px; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 11px; }

    .header { margin-bottom: 14px; }
    .header h1 { font-size: 18px; margin: 0 0 2px 0; color: #111827; }
    .header .meta { font-size: 10px; color: #6b7280; }

    .section-title {
        font-size: 12px; font-weight: bold; color: #111827;
        margin: 16px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb;
    }

    /* ---- KPI cards (3 cols x 2 rows via table) ---- */
    table.kpi { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
    table.kpi td {
        width: 33.33%; background: #f9fafb; border: 1px solid #e5e7eb; border-top: 3px solid #10b981;
        border-radius: 4px; padding: 8px 10px; vertical-align: top;
    }
    table.kpi td.blue   { border-top-color: #3b82f6; }
    table.kpi td.red    { border-top-color: #ef4444; }
    table.kpi td.purple { border-top-color: #8b5cf6; }
    table.kpi td.amber  { border-top-color: #f59e0b; }
    .kpi-label { font-size: 9.5px; color: #6b7280; margin: 0 0 3px 0; }
    .kpi-value { font-size: 15px; font-weight: bold; color: #111827; margin: 0; }

    /* ---- Revenue bar chart ---- */
    table.chart { width: 100%; border-collapse: collapse; }
    table.chart td { text-align: center; vertical-align: bottom; padding: 0 3px; }
    .bar-track { height: 90px; vertical-align: bottom; }
    .bar { background: #10b981; width: 60%; margin: 0 auto; }
    .bar-label { font-size: 8px; color: #6b7280; padding-top: 3px; }
    .bar-value { font-size: 7.5px; color: #374151; }

    /* ---- Distribution bars (status / payment) ---- */
    .dist-row { margin-bottom: 6px; }
    table.dist { width: 100%; border-collapse: collapse; }
    table.dist td { padding: 2px 0; vertical-align: middle; }
    .dist-label { width: 90px; font-size: 9.5px; color: #374151; }
    .dist-bar-cell { }
    .dist-bar-bg { background: #f3f4f6; border-radius: 3px; height: 12px; width: 100%; }
    .dist-bar-fill { height: 12px; border-radius: 3px; color: #fff; font-size: 8px; text-align: right; padding-right: 4px; }
    .dist-count { width: 55px; text-align: right; font-size: 9px; color: #6b7280; }

    /* ---- Tables ---- */
    table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.data th {
        background: #f3f4f6; text-align: left; font-size: 9.5px; color: #374151;
        padding: 5px 6px; border-bottom: 1px solid #e5e7eb;
    }
    table.data td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; font-size: 9.5px; }
    .badge {
        display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px;
        color: #fff;
    }
    .two-col td { vertical-align: top; width: 50%; padding-right: 8px; }

    .footer-note { margin-top: 16px; font-size: 8.5px; color: #9ca3af; }
</style>
</head>
<body>

    <div class="header">
        <h1>Báo cáo tổng quan Dashboard</h1>
        <div class="meta">Kỳ: {{ $periodLabel }} &nbsp;•&nbsp; Xuất lúc: {{ $generatedAt }}</div>
    </div>

    <div class="section-title">Chỉ số tổng quan</div>
    <table class="kpi">
        <tr>
            <td>
                <p class="kpi-label">Tổng doanh thu</p>
                <p class="kpi-value">{{ number_format($totalRevenue, 0, ',', '.') }}đ</p>
            </td>
            <td class="red">
                <p class="kpi-label">Đơn chờ xử lý</p>
                <p class="kpi-value">{{ $newOrders }}</p>
            </td>
            <td class="blue">
                <p class="kpi-label">Khách hàng</p>
                <p class="kpi-value">{{ $totalUsers }}</p>
            </td>
        </tr>
        <tr>
            <td class="amber">
                <p class="kpi-label">Sản phẩm hoạt động</p>
                <p class="kpi-value">{{ $activeProducts }}</p>
            </td>
            <td class="purple">
                <p class="kpi-label">Đơn hôm nay</p>
                <p class="kpi-value">{{ $todayOrders }}</p>
            </td>
            <td>
                <p class="kpi-label">Doanh thu hôm nay</p>
                <p class="kpi-value">{{ number_format($todayRevenue, 0, ',', '.') }}đ</p>
            </td>
        </tr>
    </table>

    <div class="section-title">Doanh thu theo ngày</div>
    <table class="chart">
        <tr>
            @foreach($chartRaw as $r)
                @php $h = $maxRevenue > 0 ? max(2, round(($r->revenue / $maxRevenue) * 90)) : 2; @endphp
                <td style="width: {{ 100 / max(count($chartRaw), 1) }}%;">
                    <div class="bar-track">
                        <div class="bar" style="height: {{ $h }}px;"></div>
                    </div>
                    <div class="bar-value">{{ $r->revenue >= 1000000 ? round($r->revenue / 1000000, 1) . 'tr' : number_format($r->revenue, 0, ',', '.') }}</div>
                    <div class="bar-label">{{ \Carbon\Carbon::parse($r->date)->format('d/m') }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="two-col" style="width:100%; border-collapse: collapse;">
        <tr>
            <td>
                <div class="section-title">Phân bổ trạng thái đơn hàng</div>
                <table class="dist">
                    @foreach($statusDistribution as $s)
                    <tr>
                        <td class="dist-label">{{ $s['label'] }}</td>
                        <td class="dist-bar-cell">
                            <div class="dist-bar-bg">
                                <div class="dist-bar-fill" style="width: {{ max($s['pct'], 4) }}%; background: {{ $s['color'] }};">
                                    {{ $s['pct'] > 8 ? $s['pct'].'%' : '' }}
                                </div>
                            </div>
                        </td>
                        <td class="dist-count">{{ $s['count'] }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
            <td>
                <div class="section-title">Phương thức thanh toán</div>
                <table class="dist">
                    @foreach($paymentDistribution as $p)
                    <tr>
                        <td class="dist-label">{{ $p['label'] }}</td>
                        <td class="dist-bar-cell">
                            <div class="dist-bar-bg">
                                <div class="dist-bar-fill" style="width: {{ max($p['pct'], 4) }}%; background: {{ $p['color'] }};">
                                    {{ $p['pct'] > 8 ? $p['pct'].'%' : '' }}
                                </div>
                            </div>
                        </td>
                        <td class="dist-count">{{ $p['count'] }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Đơn hàng mới nhất</div>
    <table class="data">
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Giá trị</th>
                <th>Trạng thái</th>
                <th>Ngày</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentOrders as $o)
            <tr>
                <td>{{ $o->tracking_number ?? '#'.$o->id }}</td>
                <td>{{ $o->customer_name }}</td>
                <td>{{ number_format($o->total_amount + $o->shipping_fee - $o->discount_amount, 0, ',', '.') }}đ</td>
                <td>{{ $statusLabels[$o->status] ?? $o->status }}</td>
                <td>{{ $o->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center; color:#9ca3af;">Chưa có đơn hàng</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Cảnh báo tồn kho thấp</div>
    <table class="data">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>SKU</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lowStockProducts as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->sku ?? 'N/A' }}</td>
                <td>{{ $p->stock }}</td>
                <td>
                    @if((int) $p->stock === 0)
                        <span class="badge" style="background:#ef4444;">Hết hàng</span>
                    @else
                        <span class="badge" style="background:#f59e0b;">Sắp hết</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; color:#9ca3af;">Tất cả sản phẩm đủ hàng</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">Báo cáo được tạo tự động từ hệ thống Electro Shop Admin.</div>

</body>
</html>