<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * GET /api/admin/contact-messages?per_page=15&unread=1
     */
    public function index(Request $request)
    {
        $query = ContactMessage::with('user:id,name,email')->latest();

        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $messages = $query->paginate($perPage);

        $unreadCount = ContactMessage::where('is_read', false)->count();

        return response()->json([
            'data'         => $messages->items(),
            'current_page' => $messages->currentPage(),
            'last_page'    => $messages->lastPage(),
            'total'        => $messages->total(),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * GET /api/admin/contact-messages/{id}
     * Xem chi tiết + tự động đánh dấu đã đọc
     */
    public function show($id)
    {
        $message = ContactMessage::with('user:id,name,email')->findOrFail($id);

        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json($message);
    }

    /**
     * PATCH /api/admin/contact-messages/{id}/toggle-read
     */
    public function toggleRead($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['is_read' => !$message->is_read]);

        return response()->json([
            'message' => $message->is_read ? 'Đã đánh dấu đã đọc' : 'Đã đánh dấu chưa đọc',
            'is_read' => $message->is_read,
        ]);
    }

    /**
     * DELETE /api/admin/contact-messages/{id}
     */
    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xóa tin nhắn liên hệ']);
    }
}