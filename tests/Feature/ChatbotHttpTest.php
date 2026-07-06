<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test tầng HTTP (route + controller) của chatbot — khác với
 * ChatbotResponseServiceTest (test thẳng vào service, bỏ qua HTTP layer).
 * File này đảm bảo: route thật hoạt động, dữ liệu được lưu đúng vào DB,
 * validation đúng, và rate limiting (throttle) đã cấu hình thực sự có tác
 * dụng — không chỉ nằm trên giấy trong routes/api.php.
 *
 * llm_provider luôn ép về null để không tốn quota Groq free khi chạy test.
 */
class ChatbotHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['chatbot.llm_provider' => null]);
    }

    public function test_anonymous_user_can_send_message_and_receives_reply(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'chào shop',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['session_token', 'reply', 'intent'])
            ->assertJsonPath('intent', 'greeting');

        $this->assertDatabaseCount('chat_conversations', 1);
        // 1 tin nhắn khách + 1 tin nhắn bot = 2
        $this->assertDatabaseCount('chat_messages', 2);
    }

    public function test_reusing_session_token_continues_same_conversation(): void
    {
        $first = $this->postJson('/api/chatbot/message', ['message' => 'chào shop']);
        $token = $first->json('session_token');

        $this->postJson('/api/chatbot/message', [
            'message'       => 'laptop 15 đến 20 triệu',
            'session_token' => $token,
        ])->assertOk();

        // Vẫn chỉ 1 conversation (không tạo mới) nhưng đã có 4 tin nhắn (2 lượt hỏi-đáp)
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 4);
    }

    public function test_invalid_session_token_creates_new_conversation_instead_of_erroring(): void
    {
        $response = $this->postJson('/api/chatbot/message', [
            'message'       => 'chào shop',
            'session_token' => 'token-khong-ton-tai',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('chat_conversations', 1);
        // Token trả về phải KHÁC token rác gửi lên (được sinh mới)
        $this->assertNotSame('token-khong-ton-tai', $response->json('session_token'));
    }

    public function test_message_is_required(): void
    {
        $this->postJson('/api/chatbot/message', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_message_over_1000_chars_is_rejected(): void
    {
        $this->postJson('/api/chatbot/message', [
            'message' => str_repeat('a', 1001),
        ])->assertStatus(422)->assertJsonValidationErrors('message');
    }

    public function test_history_endpoint_returns_messages_for_valid_token(): void
    {
        $send = $this->postJson('/api/chatbot/message', ['message' => 'chào shop']);
        $token = $send->json('session_token');

        $history = $this->getJson("/api/chatbot/history/{$token}");

        $history->assertOk();
        $this->assertCount(2, $history->json('messages'));
    }

    public function test_history_endpoint_returns_empty_for_unknown_token(): void
    {
        $this->getJson('/api/chatbot/history/khong-ton-tai')
            ->assertOk()
            ->assertJson(['messages' => []]);
    }

    public function test_order_status_via_http_requires_real_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/chatbot/message', [
            'message' => 'đơn hàng #999',
        ]);

        $response->assertOk()->assertJsonPath('intent', 'order_status');
        // Không có đơn nào -> phải báo không tìm thấy, không throw lỗi 500
        $this->assertStringContainsString('không tìm thấy đơn hàng', $response->json('reply'));
    }

    public function test_message_endpoint_is_rate_limited(): void
    {
        // Route khai báo throttle:20,1 -> request thứ 21 trong cùng phút phải bị chặn (429)
        for ($i = 1; $i <= 20; $i++) {
            $this->postJson('/api/chatbot/message', ['message' => 'chào shop'])
                ->assertOk();
        }

        $this->postJson('/api/chatbot/message', ['message' => 'chào shop'])
            ->assertStatus(429);
    }
}