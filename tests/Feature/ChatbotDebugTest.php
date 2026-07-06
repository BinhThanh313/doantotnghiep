<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File CHẨN ĐOÁN TẠM THỜI — xoá sau khi debug xong.
 * In ra toàn bộ response thật (status, headers, body) của 2 lần gọi liên
 * tiếp cùng route, để xác định 404 đang gặp là loại gì.
 */
class ChatbotDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_two_consecutive_calls(): void
    {
        config(['chatbot.llm_provider' => null]);

        echo "\n\n===== LẦN GỌI 1 =====\n";
        $r1 = $this->postJson('/api/chatbot/message', ['message' => 'chào shop']);
        echo "Status: " . $r1->status() . "\n";
        echo "Content: " . $r1->content() . "\n";

        echo "\n===== LẦN GỌI 2 =====\n";
        $r2 = $this->postJson('/api/chatbot/message', ['message' => 'chào shop']);
        echo "Status: " . $r2->status() . "\n";
        echo "Content: " . $r2->content() . "\n";

        $this->assertTrue(true); // luôn pass, chỉ để lấy output
    }
}