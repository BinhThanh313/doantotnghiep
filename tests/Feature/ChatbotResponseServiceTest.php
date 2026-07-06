<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\User;
use App\Services\Chatbot\ChatbotResponseService;
use App\Services\Chatbot\LlmFallbackService;
use App\Services\Chatbot\ProductQueryParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test tầng "rule-based" của chatbot với dữ liệu thật trong DB sqlite
 * in-memory. QUAN TRỌNG: ép 'chatbot.llm_provider' => null trong suốt các
 * test này, để không tốn quota API key free và để kết quả test KHÔNG phụ
 * thuộc vào việc LLM bên ngoài có đang hoạt động hay không — những gì bot
 * làm được (product search, order lookup, FAQ) tách bạch khỏi phần LLM
 * fallback (chỉ dùng cho câu hỏi cảm tính/mở, không thể assert chính xác).
 */
class ChatbotResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): ChatbotResponseService
    {
        config(['chatbot.llm_provider' => null]);

        return new ChatbotResponseService(new ProductQueryParser(), new LlmFallbackService());
    }

    public function test_greeting_returns_greeting_intent(): void
    {
        $service = $this->makeService();

        $result = $service->respond('chào shop');

        $this->assertSame('greeting', $result['intent']);
        $this->assertStringContainsString('trợ lý mua sắm', $result['reply']);
    }

    public function test_product_search_returns_matching_products_only(): void
    {
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);

        $match = Product::create([
            'category_id' => $category->id, 'name' => 'Laptop Dell XPS 13', 'slug' => 'dell-xps-13',
            'price' => 18_000_000, 'stock' => 5, 'is_active' => true,
        ]);
        ProductSpecification::create([
            'product_id' => $match->id, 'group_name' => 'Cấu hình', 'label' => 'RAM', 'value' => '16', 'unit' => 'GB',
        ]);

        Product::create([
            'category_id' => $category->id, 'name' => 'Laptop Acer rẻ', 'slug' => 'acer-re',
            'price' => 8_000_000, 'stock' => 5, 'is_active' => true,
        ]);

        $service = $this->makeService();
        $result = $service->respond('laptop 15 đến 20 triệu');

        $this->assertSame('product_search', $result['intent']);
        $this->assertStringContainsString('Dell XPS 13', $result['reply']);
        $this->assertStringNotContainsString('Acer rẻ', $result['reply']);
    }

    public function test_product_search_no_match_gives_honest_no_result_reply(): void
    {
        Category::create(['name' => 'Laptop', 'slug' => 'laptop']);

        $service = $this->makeService();
        $result = $service->respond('laptop trên 100 triệu');

        $this->assertSame('product_search', $result['intent']);
        $this->assertStringContainsString('không tìm thấy sản phẩm', $result['reply']);
    }

    public function test_faq_matches_known_policy_keyword(): void
    {
        $service = $this->makeService();
        $result = $service->respond('chính sách đổi trả như thế nào');

        $this->assertSame('policy_faq', $result['intent']);
        $this->assertStringContainsString('7 ngày', $result['reply']);
    }

    public function test_order_lookup_requires_login(): void
    {
        $service = $this->makeService();
        $result = $service->respond('cho tôi hỏi đơn hàng #5', null);

        $this->assertSame('order_status', $result['intent']);
        $this->assertStringContainsString('đăng nhập', $result['reply']);
    }

    public function test_order_lookup_only_returns_own_order(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();

        $order = Order::create([
            'user_id' => $owner->id, 'customer_name' => 'A', 'customer_email' => 'a@test.com',
            'customer_phone' => '0900000000', 'address' => 'Test address',
            'invoice_number' => 'INV-001', 'total_amount' => 1_000_000,
            'status' => 'processing', 'tracking_number' => 'ORD-TEST1234',
        ]);

        $service = $this->makeService();

        // Khách khác đăng nhập, hỏi đúng mã đơn của người khác -> KHÔNG được thấy
        $resultOther = $service->respond('đơn hàng ORD-TEST1234', $other);
        $this->assertStringContainsString('không tìm thấy đơn hàng', $resultOther['reply']);

        // Chủ đơn hỏi đúng mã của mình -> thấy đúng trạng thái
        $resultOwner = $service->respond('đơn hàng ORD-TEST1234', $owner);
        $this->assertStringContainsString('Đang chuẩn bị hàng', $resultOwner['reply']);
    }

    public function test_order_lookup_regex_does_not_misfire_on_unrelated_number(): void
    {
        $owner = User::factory()->create();

        // Câu này có số "3" nhưng không phải ý định tra cứu đơn hàng theo ID 3.
        // Khoảng cách giữa "đơn hàng" và "3" vượt quá phạm vi regex cho phép
        // -> KHÔNG bị bắt thành order lookup, rơi xuống llm_fallback an toàn
        // thay vì tra nhầm ID:3. Đây là hành vi đúng mong muốn sau khi siết regex.
        $service = $this->makeService();
        $result = $service->respond('đơn hàng của tôi có 3 sản phẩm đúng không nhỉ', $owner);

        $this->assertSame('llm_fallback', $result['intent']);
    }

    public function test_llm_disabled_gives_safe_default_reply_without_calling_api(): void
    {
        $service = $this->makeService();

        // Câu hỏi mở, không khớp category/brand/giá/spec/FAQ/order
        // -> rơi vào llm_fallback, nhưng vì llm_provider = null nên phải
        // trả về câu trả lời mặc định an toàn, KHÔNG được gọi ra ngoài internet.
        $result = $service->respond('bạn có thể tư vấn giúp tôi không');

        $this->assertSame('llm_fallback', $result['intent']);
        $this->assertStringContainsString('chưa hiểu rõ câu hỏi', $result['reply']);
    }
}