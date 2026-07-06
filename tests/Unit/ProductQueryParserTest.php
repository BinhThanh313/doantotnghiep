<?php

namespace Tests\Unit;

use App\Services\Chatbot\ProductQueryParser;
use PHPUnit\Framework\TestCase;

/**
 * Test lớp rule-based parser — không đụng DB, không gọi LLM, chạy được
 * trong vài mili-giây. Đây là phần LOGIC CỦA BẠN (không phụ thuộc API key
 * free hay hạ tầng bên ngoài), nên test kỹ phần này là nơi đáng tin cậy
 * nhất để chứng minh chatbot "hoạt động đúng ý" ở tầng xử lý chính xác.
 */
class ProductQueryParserTest extends TestCase
{
    private ProductQueryParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ProductQueryParser();
    }

    public function test_parses_price_range(): void
    {
        $filters = $this->parser->parse('laptop 15 đến 20 triệu');

        $this->assertSame(15_000_000, $filters['price_min']);
        $this->assertSame(20_000_000, $filters['price_max']);
        $this->assertSame('Laptop', $filters['category']);
    }

    public function test_parses_duoi_price(): void
    {
        $filters = $this->parser->parse('điện thoại dưới 10 triệu');

        $this->assertNull($filters['price_min']);
        $this->assertSame(10_000_000, $filters['price_max']);
        $this->assertSame('Điện thoại', $filters['category']);
    }

    public function test_parses_tren_price(): void
    {
        $filters = $this->parser->parse('laptop trên 25 triệu');

        $this->assertSame(25_000_000, $filters['price_min']);
        $this->assertNull($filters['price_max']);
    }

    public function test_parses_khoang_price_with_tolerance(): void
    {
        $filters = $this->parser->parse('điện thoại khoảng 20 triệu');

        // "khoảng X" -> hiểu là <= X * 1.15 theo thiết kế hiện tại
        $this->assertSame((int) round(20_000_000 * 1.15), $filters['price_max']);
    }

    public function test_parses_cpu_ram_storage_together(): void
    {
        $filters = $this->parser->parse('laptop core i7 ram tối thiểu 12 ổ cứng 512gb');

        $this->assertSame('Laptop', $filters['category']);

        $specLabels = array_column($filters['specs'], 'label');
        $this->assertContains('CPU', $specLabels);
        $this->assertContains('RAM', $specLabels);
        $this->assertContains('STORAGE', $specLabels);

        $ramSpec = collect($filters['specs'])->firstWhere('label', 'RAM');
        $this->assertSame('>=', $ramSpec['operator']);
        $this->assertSame(12, $ramSpec['value']);
    }

    public function test_detects_brand_apple_maps_to_product_line_pattern(): void
    {
        $filters = $this->parser->parse('tìm sản phẩm apple giá rẻ');

        $this->assertSame('iPhone|iPad|MacBook|Apple Watch', $filters['brand']);
    }

    public function test_detects_specific_brand(): void
    {
        $filters = $this->parser->parse('samsung màn hình lớn');

        $this->assertSame('Samsung', $filters['brand']);
    }

    public function test_tb_storage_converted_to_gb(): void
    {
        $filters = $this->parser->parse('laptop 1tb');

        $storageSpec = collect($filters['specs'])->firstWhere('label', 'STORAGE');
        $this->assertNotNull($storageSpec);
        $this->assertSame(1000, $storageSpec['value']);
    }

    public function test_is_product_search_true_when_any_filter_present(): void
    {
        $this->assertTrue($this->parser->isProductSearch(['category' => 'Laptop', 'brand' => null, 'price_min' => null, 'price_max' => null, 'specs' => []]));
        $this->assertTrue($this->parser->isProductSearch(['category' => null, 'brand' => null, 'price_min' => null, 'price_max' => 5_000_000, 'specs' => []]));
    }

    public function test_is_product_search_false_when_no_filter_present(): void
    {
        $this->assertFalse($this->parser->isProductSearch(['category' => null, 'brand' => null, 'price_min' => null, 'price_max' => null, 'specs' => []]));
    }

    public function test_no_false_positive_on_plain_greeting(): void
    {
        // Câu chào không được lẫn thành tìm sản phẩm dù có thể chứa số/ký tự trùng
        $filters = $this->parser->parse('chào shop');
        $this->assertFalse($this->parser->isProductSearch($filters));
    }
}