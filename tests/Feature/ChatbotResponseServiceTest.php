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

    public function test_follow_up_question_without_matched_product_asks_to_clarify_instead_of_calling_llm(): void
    {
        // Mô phỏng đúng tình huống thực tế đã gặp: tin nhắn bot trước đó
        // không chứa tên sản phẩm khớp CHÍNH XÁC với DB (VD do LLM viết lại
        // hoặc chỉ là câu chào chung chung), sau đó khách hỏi tiếp kiểu so
        // sánh/đánh giá ("cái này có đáng mua không"). Phải trả lời cố định
        // hỏi lại khách, TUYỆT ĐỐI không được rơi xuống llm_fallback kèm
        // context bestseller (đây chính là bug thực tế: LLM cứ có context là
        // liệt kê sản phẩm ra dù câu hỏi không yêu cầu liệt kê).
        $history = [
            ['sender' => 'user', 'message' => 'tư vấn giúp mình cái gì đó tốt'],
            ['sender' => 'bot', 'message' => 'Hiện tại chúng tôi có một số sản phẩm nổi bật, bạn quan tâm loại nào?'],
            ['sender' => 'user', 'message' => 'cái này có đáng mua không'],
        ];

        $service = $this->makeService();
        $result = $service->respond('cái này có đáng mua không', null, $history);

        $this->assertSame('clarify_product', $result['intent']);
        $this->assertStringContainsString('sản phẩm nào', $result['reply']);
    }

    public function test_follow_up_question_with_matched_product_uses_specific_product_context(): void
    {
        // Ngược lại: khi tin nhắn bot trước đó CÓ chứa tên sản phẩm khớp
        // chính xác với DB, phải nhận diện được và đi tiếp xuống llm_fallback
        // (không bị chặn thành clarify_product).
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        Product::create([
            'category_id' => $category->id, 'name' => 'MacBook Air M2', 'slug' => 'macbook-air-m2',
            'price' => 28_000_000, 'stock' => 5, 'is_active' => true, 'is_bestseller' => true,
        ]);

        $history = [
            ['sender' => 'user', 'message' => 'laptop apple'],
            ['sender' => 'bot', 'message' => 'Mình tìm thấy: MacBook Air M2 — 28.000.000đ'],
            ['sender' => 'user', 'message' => 'cái này có đáng mua không'],
        ];

        $service = $this->makeService();
        $result = $service->respond('cái này có đáng mua không', null, $history);

        $this->assertSame('llm_fallback', $result['intent']);
    }

    public function test_comparison_question_naming_products_explicitly_is_understood_even_with_unrelated_turns_between(): void
    {
        // Tái hiện đúng bug thực tế đã gặp khi test kịch bản defense: khách
        // nêu THẲNG tên 2 sản phẩm trong câu so sánh ("macbook air m2 với
        // dell xps 15 cái nào tốt hơn"), nhưng các lượt hội thoại NGAY TRƯỚC
        // ĐÓ lại không liên quan gì tới sản phẩm (hỏi COD, tra đơn hàng thất
        // bại) — bug cũ: bot chỉ soi 3 tin nhắn BOT gần nhất để tìm sản phẩm
        // "đang được bàn tới", bỏ qua hoàn toàn tên sản phẩm khách vừa gõ
        // ngay trong câu hỏi, nên luôn hỏi lại dù thông tin đã có sẵn.
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        Product::create([
            'category_id' => $category->id, 'name' => 'MacBook Air M2', 'slug' => 'macbook-air-m2',
            'price' => 32_990_000, 'stock' => 5, 'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Dell XPS 15', 'slug' => 'dell-xps-15',
            'price' => 42_990_000, 'stock' => 5, 'is_active' => true,
        ]);

        $history = [
            ['sender' => 'user', 'message' => 'cái đầu tiên có đáng mua không'],
            ['sender' => 'bot', 'message' => 'Bạn đang hỏi về sản phẩm nào vậy? Bạn nhắc lại tên sản phẩm giúp mình để tư vấn chính xác hơn nhé.'],
            ['sender' => 'user', 'message' => 'shop có ship COD không'],
            ['sender' => 'bot', 'message' => 'Electro Shop hỗ trợ thanh toán khi nhận hàng (COD) và chuyển khoản ngân hàng qua mã QR.'],
            ['sender' => 'user', 'message' => 'đơn hàng của tôi tới đâu rồi'],
            ['sender' => 'bot', 'message' => 'Bạn vui lòng đăng nhập để mình tra cứu đúng đơn hàng của bạn nhé.'],
        ];

        $service = $this->makeService();
        $result = $service->respond('macbook air m2 với dell xps 15 cái nào tốt hơn', null, $history);

        // KHÔNG được rơi vào clarify_product — tên sản phẩm đã có sẵn ngay
        // trong câu hỏi hiện tại.
        $this->assertSame('llm_fallback', $result['intent']);
    }

    public function test_cod_question_matches_payment_faq_not_shipping_faq(): void
    {
        // "shop có ship COD không" chứa cả 'ship' lẫn 'cod' — ý khách hỏi là
        // về THANH TOÁN (có hỗ trợ COD không), không phải hỏi thời gian giao
        // hàng. Trước đây bị từ khoá 'ship' của FAQ vận chuyển nuốt mất.
        $service = $this->makeService();
        $result = $service->respond('shop có ship COD không');

        $this->assertSame('policy_faq', $result['intent']);
        $this->assertStringContainsString('COD', $result['reply']);
    }

    public function test_off_topic_weather_question_does_not_list_products(): void
    {
        // Có sẵn sản phẩm bestseller trong DB để đảm bảo nếu bug tái diễn
        // (câu hỏi lọt xuống LLM fallback kèm context bestseller) thì test
        // này thực sự có dữ liệu để phát hiện ra sai sót.
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop']);
        Product::create([
            'category_id' => $category->id, 'name' => 'Laptop Dell XPS 13', 'slug' => 'dell-xps-13',
            'price' => 18_000_000, 'stock' => 5, 'is_active' => true, 'is_bestseller' => true,
        ]);

        $service = $this->makeService();
        $result = $service->respond('trời hôm nay có đẹp không');

        // PHẢI bị chặn ở tầng rule-based (small_talk), KHÔNG được rơi xuống
        // product_search hay llm_fallback -> đảm bảo hành vi ổn định, không
        // phụ thuộc việc LLM có tuân thủ prompt hay không.
        $this->assertSame('small_talk', $result['intent']);
        $this->assertStringNotContainsString('Dell XPS 13', $result['reply']);
    }

    public function test_vague_shopping_question_still_reaches_llm_fallback(): void
    {
        // "có gì hot không" không đủ tín hiệu để parser bắt thành product
        // search (không category/brand/giá/spec) nhưng RÕ RÀNG là câu hỏi
        // mua sắm mơ hồ, cần được whitelist cho qua để LLM tư vấn dựa trên
        // context bestseller — không được bị chặn nhầm thành small_talk.
        $service = $this->makeService();
        $result = $service->respond('có gì hot không shop');

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