<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/notifications?per_page=15&unread=1
    // ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = AppNotification::where('user_id', $request->user()->id)
            ->latest();

        // Lọc chỉ lấy chưa đọc
        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $notifications = $query->paginate($perPage);

        // Số thông báo chưa đọc (dùng cho badge ở FE)
        $unreadCount = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'data'         => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
            'total'        => $notifications->total(),
            'unread_count' => $unreadCount,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PATCH /api/notifications/{id}/read
    // ──────────────────────────────────────────────────────────

    public function markRead(Request $request, int $id)
    {
        $notification = AppNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu đã đọc',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PATCH /api/notifications/read-all
    // ──────────────────────────────────────────────────────────

    public function markAllRead(Request $request)
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => "Đã đánh dấu {$count} thông báo đã đọc",
            'updated' => $count,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/notifications/{id}
    // ──────────────────────────────────────────────────────────

    public function destroy(Request $request, int $id)
    {
        AppNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/notifications/unread-count
    // ──────────────────────────────────────────────────────────

    public function unreadCount(Request $request)
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}