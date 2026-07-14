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
    public function index()
    {
        return response()->json($this->insightService->all());
    }
}
