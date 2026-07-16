<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * POST /api/chatbot/message
     * Body: { message: string, session_token?: string }
     */
    public function handle(Request $request, ChatbotResponseService $chatbot)
    {
        $data = $request->validate([
            'message'       => 'required|string|max:1000',
            'session_token' => 'nullable|string',
        ]);

        $conversation = $this->resolveConversation($request, $data['session_token'] ?? null);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender'          => 'user',
            'message'         => $data['message'],
        ]);

        $result = $chatbot->respond($data['message'], Auth::user(), $this->recentHistory($conversation));

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender'          => 'bot',
            'message'         => $result['reply'],
            'intent'          => $result['intent'],
        ]);

        return response()->json([
            'session_token' => $conversation->session_token,
            'reply'         => $result['reply'],
            'intent'        => $result['intent'],
        ]);
    }

    /**
     * GET /api/chatbot/history/{sessionToken}
     */
    public function history(string $sessionToken)
    {
        $conversation = ChatConversation::where('session_token', $sessionToken)->first();

        if (!$conversation) {
            return response()->json(['messages' => []]);
        }

        return response()->json([
            'messages' => $conversation->messages()->get(['sender', 'message', 'created_at']),
        ]);
    }

    private function resolveConversation(Request $request, ?string $sessionToken): ChatConversation
    {
        if ($sessionToken) {
            $conversation = ChatConversation::where('session_token', $sessionToken)->first();
            if ($conversation) {
                return $conversation;
            }
        }

        return ChatConversation::create([
            'user_id'       => Auth::id(),
            'session_token' => (string) Str::uuid(),
        ]);
    }

    /**
     * Lấy vài lượt trao đổi gần nhất (trước tin nhắn vừa gửi) để LLM fallback
     * hiểu được câu hỏi nối tiếp kiểu "cái nào tốt hơn", "sản phẩm đầu tiên
     * giá bao nhiêu"... — chatbot vốn không lưu state, phải truyền lại thủ
     * công mỗi lần gọi.
     */
    private function recentHistory(ChatConversation $conversation, int $limit = 6): array
    {
        return $conversation->messages()
            // reorder() xoá orderBy('created_at') có sẵn trong relation
            // ChatConversation::messages() trước khi áp lại đúng thứ tự —
            // nếu không, Laravel cộng dồn thành "ORDER BY created_at ASC,
            // id DESC" và limit() sẽ lấy nhầm N tin CŨ NHẤT thay vì mới nhất.
            ->reorder('id', 'desc')
            ->limit($limit)
            ->get(['sender', 'message'])
            ->reverse()
            ->map(fn ($m) => ['sender' => $m->sender, 'message' => $m->message])
            ->values()
            ->all();
    }
}