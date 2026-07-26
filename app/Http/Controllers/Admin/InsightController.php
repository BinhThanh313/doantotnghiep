<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminInsightService;
use Illuminate\Http\Request;

class InsightController extends Controller
{
    public function __construct(private AdminInsightService $insightService)
    {
    }

    /**
     * Trả về toàn bộ 7 nhóm insight cùng lúc — dùng cho trang dashboard insight.
     */
    public function index(Request $request)
    {
        $cacheKey = 'admin_dashboard_insights';

        if ($request->has('refresh') || $request->input('force') === 'true') {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        // Cache toàn bộ kết quả nặng nề trong 15 phút.
        // Điều này khắc phục triệt để độ trễ mạng (Network Latency) khi Web Server 
        // và Database (Supabase) nằm cách xa nhau về mặt địa lý.
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function () {
            // Chuyển đổi thành Array chuẩn để tránh lỗi __PHP_Incomplete_Class_Name khi Cache unserialize Collection
            $raw = $this->insightService->all();
            return json_decode(json_encode($raw), true);
        });

        return response()->json($data);
    }
}
