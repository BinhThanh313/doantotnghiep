<?php

namespace App\Services\Chatbot;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotResponseService
{
    private ProductQueryParser $parser;
    private LlmFallbackService $llm;

    public function __construct(ProductQueryParser $parser, LlmFallbackService $llm)
    {
        $this->parser = $parser;
        $this->llm    = $llm;
    }

    /**
     * @param array<int, array{sender: string, message: string}> $history Vài lượt trao đổi gần nhất (kể cả câu vừa hỏi)
     * @return array{intent: string, reply: string}
     */
    public function respond(string $message, ?User $user = null, array $history = []): array
    {
        if ($this->isGreeting($message)) {
            return [
                'intent' => 'greeting',
                'reply'  => 'Xin chào! Mình là trợ lý mua sắm của Electro Shop. Bạn cần tìm sản phẩm gì hôm nay? '
                          . 'Bạn có thể hỏi mình theo kiểu: "laptop 15-20 triệu core i7 ram 12" hoặc "điện thoại Apple dưới 25 triệu".',
            ];
        }

        // Câu hỏi kiểu so sánh/nối tiếp ("cái nào tốt hơn", "laptop nào tốt
        if ($this->isFollowUpComparison($message)) {
            $recentProducts = $this->findRecentlyMentionedProducts($history, $message);
            $productContext = "SẢN PHẨM LIÊN QUAN TRONG CUỘC TRÒ CHUYỆN:\n";
            if ($recentProducts->isNotEmpty()) {
                $productContext .= $this->buildProductContextWithSpecs($recentProducts);
            } else {
                $productContext .= "(Không có)\n";
            }
            $productContext .= "\nSẢN PHẨM BÁN CHẠY (GỢI Ý):\n";
            $productContext .= $this->buildFallbackContext();

            $globalContext = $this->buildGlobalContext($user) . "\n\n" . $productContext;
            
            return [
                'intent' => 'llm_fallback',
                'reply'  => $this->llm->reply($message, $globalContext, $history),
            ];
        }

        $filters = $this->parser->parse($message);

        if ($this->parser->isProductSearch($filters)) {
            return $this->handleProductSearch($filters, $message);
        }

        if ($orderId = $this->extractOrderId($message)) {
            return $this->handleOrderLookup($orderId, $user);
        }

        if ($faqReply = $this->matchFaq($message)) {
            return ['intent' => 'policy_faq', 'reply' => $faqReply];
        }

        // Tới đây nghĩa là KHÔNG có category/brand/giá/spec, không phải tra
        // đơn hàng, không khớp FAQ. Giao phó hoàn toàn cho LLM tự quyết định.
        $recentProducts = $this->findRecentlyMentionedProducts($history, $message);
        
        $productContext = "SẢN PHẨM LIÊN QUAN TRONG CUỘC TRÒ CHUYỆN:\n";
        if ($recentProducts->isNotEmpty()) {
            $productContext .= $this->buildProductContextWithSpecs($recentProducts);
        } else {
            $productContext .= "(Không có)\n";
        }

        $productContext .= "\nSẢN PHẨM BÁN CHẠY (GỢI Ý):\n";
        $productContext .= $this->buildFallbackContext();
        
        $globalContext = $this->buildGlobalContext($user) . "\n\n" . $productContext;

        return [
            'intent' => 'llm_fallback',
            'reply'  => $this->llm->reply($message, $globalContext, $history),
        ];
    }

    /**
     * Tìm các sản phẩm ĐANG được bàn tới trong vài tin nhắn bot gần nhất,
     * bằng cách so khớp trực tiếp với tên sản phẩm thật trong DB — thay vì
     * chỉ trích theo định dạng gạch đầu dòng cố định. Cách này đúng bất kể
     * bot trả lời theo dạng liệt kê (rule-based) hay văn xuôi (LLM diễn giải
     * câu hỏi cảm tính), tránh trường hợp bot "quên" sản phẩm thật đang bàn
     * và tự chuyển sang nói về sản phẩm bestseller không liên quan.
     *
     * LƯU Ý: so khớp bằng str_contains trên NGUYÊN VĂN tin nhắn bot, nên nếu
     * LLM (ở lượt trả lời trước) diễn đạt lại tên sản phẩm không khớp 100%
     * ký tự với tên thật trong DB (thêm/bớt khoảng trắng, viết tắt...), hàm
     * này sẽ không match được — đây là lý do bắt buộc phải coi collection
     * rỗng là 1 khả năng THỰC SỰ xảy ra ở nơi gọi hàm này, không được coi là
     * trường hợp hiếm rồi phó mặc cho LLM tự xử lý.
     */
    private function findRecentlyMentionedProducts(array $history, string $currentMessage = '')
    {
        $recentBotText = collect($history)
            ->filter(fn ($m) => $m['sender'] === 'bot')
            ->take(-3) // 3 tin nhắn bot gần nhất
            ->pluck('message')
            ->implode(' ');

        // QUAN TRỌNG: gộp thêm CHÍNH câu hỏi hiện tại của khách. Khách hoàn
        // toàn có thể nêu thẳng tên sản phẩm ngay trong câu so sánh (VD
        // "macbook air m2 với dell xps 15 cái nào tốt hơn") mà không cần bot
        // vừa nhắc tới chúng trước đó. Nếu chỉ dựa vào lịch sử bot, các lượt
        // hỏi xen giữa không liên quan sản phẩm (hỏi COD, tra đơn hàng...)
        // sẽ khiến bot "quên" mất sản phẩm đang được nêu tên rành rành trong
        // câu hỏi, dù đó là tín hiệu chắc chắn nhất có thể có.
        $haystack = trim($recentBotText . ' ' . $currentMessage);

        if ($haystack === '') {
            return collect();
        }

        // So khớp KHÔNG phân biệt hoa/thường: tên sản phẩm do BOT sinh ra
        // (rule-based) khớp nguyên văn DB nên vốn không cần lower-case, nhưng
        // câu khách TỰ GÕ (VD "macbook air m2") gần như luôn không viết hoa
        // đúng chuẩn ("MacBook Air M2"), nên bắt buộc phải so khớp lower-case
        // ở đây, khác với chỗ so khớp cũ (chỉ soát tin bot) không cần việc này.
        $haystackLower = mb_strtolower($haystack);

        // Chỉ cần so khớp trong phạm vi ~58 sản phẩm active, chi phí không đáng kể
        $matchedNames = Product::where('is_active', true)
            ->pluck('name')
            ->filter(fn ($name) => str_contains($haystackLower, mb_strtolower($name)));

        if ($matchedNames->isEmpty()) {
            return collect();
        }

        // Giữ ĐÚNG thứ tự sản phẩm xuất hiện trong văn bản (không phải thứ
        // tự ngẫu nhiên MySQL trả về từ whereIn), để phục vụ các câu hỏi
        // tham chiếu theo thứ tự như "cái đầu tiên", "cái thứ hai" — nếu
        // không giữ thứ tự này, kể cả khi tìm đúng sản phẩm, LLM vẫn không
        // có cách nào biết "đầu tiên" nghĩa là sản phẩm nào.
        $orderedNames = $matchedNames
            ->sortBy(fn ($name) => mb_stripos($haystackLower, mb_strtolower($name)))
            ->values();

        $products = Product::with(['category', 'specifications', 'activeFlashSaleItem'])
            ->whereIn('name', $orderedNames)
            ->get();

        return $products->sortBy(fn ($p) => $orderedNames->search($p->name))->values();
    }

    private function formatHistory(array $history): string
    {
        return ''; // Đã được xử lý thành mảng messages trong LLM Service
    }

    // ==================== CHÀO HỎI ====================

    private function isFollowUpComparison(string $message): bool
    {
        $text = mb_strtolower($message);
        $patterns = [
            'tốt hơn', 'tốt nhất', 'ngon hơn',
            'so sánh', 'đáng mua hơn', 'nên chọn cái',
            'so với nhau', 'so với cái', 'giữa hai', 'giữa 2', 'hai cái này',
            'cái này với cái kia', 'nên mua cái nào', 'khác nhau thế nào',
            'khác nhau chỗ nào', 'khác gì nhau', 'ưu nhược điểm', 'nên lấy cái',
            'cái đó', 'cái này', 'con đó', 'con này', 'sản phẩm đó', 'sản phẩm này',
            'hàng đó', 'hàng này', 'món đó', 'món này', 'em đó', 'em này',
            'đáng mua không', 'có đáng mua', 'có nên mua', 'có tốt không',
            'có ổn không', 'có đáng không', 'mua được không',
            'cái đầu', 'cái thứ', 'cái cuối', 'mấy cái', 'những cái',
        ];
        foreach ($patterns as $p) {
            if (str_contains($text, $p)) {
                return true;
            }
        }
        return false;
    }

    private function isGreeting(string $message): bool
    {
        $text = trim(mb_strtolower($message));

        // Bỏ các từ đệm phổ biến ở CUỐI câu ("ơi", "nhé", "nha", "bạn",
        // "shop", "ạ") trước khi so khớp, để nhận ra được các biến thể như
        // "chào shop ơi", "hi bạn nhé" — vẫn giữ nguyên tắc so khớp NGUYÊN
        // CỤM sau khi bỏ đệm, không đoán mò nội dung câu, nên không tăng
        // rủi ro nhận nhầm câu dài chứa "hi" ở giữa (VD "thích").
        $normalized = $text;
        do {
            $before = $normalized;
            $normalized = trim(preg_replace('/\s*(ơi|nhé|nha|bạn|shop|ạ)\b\s*$/u', '', $normalized) ?? $normalized);
        } while ($normalized !== $before);

        $greetings = ['hi', 'hello', 'hey', 'chào', 'xin chào', 'alo', 'chào bạn', 'chào shop'];
        return in_array($text, $greetings, true) || in_array($normalized, $greetings, true);
    }

    // ==================== NGOÀI LỀ / PHIẾM ====================



    // ==================== TÌM SẢN PHẨM ====================

    private array $subjectiveQualifiers = [
        'phù hợp cho', 'phù hợp với', 'dành cho', 'tốt cho', 'thích hợp cho',
        'thích hợp với', 'nên chọn', 'nên mua', 'phù hợp nhất',
        'để chơi', 'để học', 'để làm', 'chụp ảnh', 'chơi game', 'cái nào',
        'so sánh', 'hay là', 'tốt hơn', 'đẹp hơn', 'trâu hơn', 'mượt hơn',
        'đáng mua', 'tư vấn', 'ngon hơn', 'khác nhau',
    ];

    private function handleProductSearch(array $filters, string $originalMessage): array
    {
        $query = Product::with(['category', 'specifications', 'activeFlashSaleItem'])->where('is_active', true);

        if ($filters['category']) {
            $query->whereHas('category', fn ($q) => $q->where('name', $filters['category']));
        }
        if ($filters['brand']) {
            // brand có thể là "iPhone|iPad|MacBook" (nhiều tên gộp của 1 hãng)
            $names = explode('|', $filters['brand']);
            $query->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhere('name', 'like', '%' . $name . '%');
                }
            });
        }
        if ($filters['price_min'] || $filters['price_max']) {
            $min = $filters['price_min'];
            $max = $filters['price_max'];
            // Khớp theo giá thường HOẶC giá Flash Sale đang chạy, để không bỏ
            // sót sản phẩm đang sale rơi vào khoảng giá khách hỏi dù giá gốc
            // nằm ngoài khoảng đó (và ngược lại).
            $query->where(function ($q) use ($min, $max) {
                $q->where(function ($qq) use ($min, $max) {
                    if ($min) $qq->where('price', '>=', $min);
                    if ($max) $qq->where('price', '<=', $max);
                })->orWhereHas('activeFlashSaleItem', function ($qq) use ($min, $max) {
                    if ($min) $qq->where('sale_price', '>=', $min);
                    if ($max) $qq->where('sale_price', '<=', $max);
                });
            });
        }

        foreach ($filters['specs'] as $spec) {
            $query->whereHas('specifications', function ($q) use ($spec) {
                if ($spec['label'] === 'STORAGE') {
                    $q->where(function ($qq) {
                        $qq->where('label', 'like', '%Ổ cứng%')
                           ->orWhere('label', 'like', '%Bộ nhớ trong%');
                    });
                } else {
                    $q->where('label', 'like', '%' . $spec['label'] . '%');
                }

                if ($spec['operator'] === 'contains') {
                    $q->where('value', 'like', '%' . $spec['value'] . '%');
                } else {
                    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
                    
                    if ($driver === 'pgsql') {
                        // PostgreSQL: Cần cờ 'g' để replace toàn bộ, và dùng AS NUMERIC tránh overflow
                        $q->whereRaw(
                            "CAST(NULLIF(REGEXP_REPLACE(value, '[^0-9]', '', 'g'), '') AS NUMERIC) >= ?",
                            [$spec['value']]
                        );
                    } else {
                        // MySQL: Mặc định replace toàn bộ, dùng AS UNSIGNED
                        $q->whereRaw(
                            "CAST(REGEXP_REPLACE(value, '[^0-9]', '') AS UNSIGNED) >= ?",
                            [$spec['value']]
                        );
                    }
                }
            });
        }

        $limit = config('chatbot.max_search_results', 5);
        $products = $query->limit($limit)->get();

        if ($products->isEmpty()) {
            return [
                'intent' => 'product_search',
                'reply'  => 'Xin lỗi, mình không tìm thấy sản phẩm nào khớp đúng yêu cầu của bạn. '
                          . 'Bạn có thể thử nới rộng khoảng giá hoặc thông số mong muốn không?',
            ];
        }

        $isSubjective = $this->hasSubjectiveQualifier($originalMessage);

        if ($isSubjective && $this->llm->isEnabled()) {
            $context = $this->buildProductContextWithSpecs($products);
            $llmReply = $this->llm->reply($originalMessage, $context);
            return ['intent' => 'product_search', 'reply' => $llmReply];
        }

        $lines = $products->map(function (Product $p) {
            return "- {$p->name} — " . $this->formatProductPrice($p)
                 . ($p->category ? " ({$p->category->name})" : '');
        })->implode("\n");

        $intro = $products->count() === 1
            ? 'Mình tìm thấy 1 sản phẩm phù hợp:'
            : "Mình tìm thấy {$products->count()} sản phẩm phù hợp:";

        // Câu hỏi có yếu tố cảm tính nhưng LLM đang tắt/quá tải -> vẫn trả
        // danh sách thật (không bịa), kèm ghi chú rõ để khách hiểu giới hạn,
        // thay vì im lặng trả lời như thể đã cá nhân hoá.
        $note = $isSubjective
            ? "\n\n(Mình liệt kê các sản phẩm khớp tiêu chí giá/thông số bạn đưa ra; phần \"phù hợp cho {$this->extractSubjectPhrase($originalMessage)}\" cần trợ lý AI để so sánh sâu hơn, hiện đang tạm thời không khả dụng.)"
            : '';

        return [
            'intent' => 'product_search',
            'reply'  => "{$intro}\n{$lines}{$note}",
        ];
    }

    private function hasSubjectiveQualifier(string $message): bool
    {
        $text = mb_strtolower($message);
        foreach ($this->subjectiveQualifiers as $q) {
            if (str_contains($text, $q)) {
                return true;
            }
        }
        return false;
    }

    private function extractSubjectPhrase(string $message): string
    {
        $text = mb_strtolower($message);
        foreach ($this->subjectiveQualifiers as $q) {
            $pos = mb_strpos($text, $q);
            if ($pos !== false) {
                return trim(mb_substr($text, $pos + mb_strlen($q)));
            }
        }
        return '';
    }

    /**
     * Định dạng giá của 1 sản phẩm để đưa vào câu trả lời của chatbot,
     * đồng bộ với giá hiển thị trên các trang khác: nếu sản phẩm đang
     * Flash Sale thì ưu tiên giá sale kèm % giảm + giá gốc, ngược lại
     * dùng giá bán thông thường. Luôn nhớ eager-load 'activeFlashSaleItem'
     * ở query lấy $p để tránh N+1.
     */
    private function formatProductPrice(Product $p): string
    {
        if ($p->is_flash_sale) {
            return number_format($p->flash_sale_price, 0, ',', '.') . 'đ'
                 . " (⚡Flash Sale -{$p->flash_sale_discount_percent}%, giá gốc "
                 . number_format($p->price, 0, ',', '.') . 'đ)';
        }

        return number_format($p->price, 0, ',', '.') . 'đ';
    }

    private function buildProductContextWithSpecs($products): string
    {
        return collect($products)->values()->map(function (Product $p) {
            $specs = $p->specifications
                ->map(fn ($s) => "      - {$s->label}: {$s->value}" . ($s->unit ? " {$s->unit}" : ''))
                ->implode("\n");

            $category = $p->category ? $p->category->name : 'Không rõ';
            
            return "- Tên sản phẩm: {$p->name}\n"
                 . "  Phân loại: {$category}\n"
                 . "  Giá bán: " . $this->formatProductPrice($p) . "\n"
                 . "  Thông số kỹ thuật:\n"
                 . ($specs ?: "      (Không có thông số)");
        })->implode("\n\n");
    }

    // ==================== TRA CỨU ĐƠN HÀNG ====================

    private function extractOrderId(string $message): ?string
    {
        $text = mb_strtolower($message);

        // 1. Luôn ưu tiên bắt trực tiếp mã đơn thật dạng "ORD-824C3233" ở bất kỳ đâu trong câu
        // không cần khách phải gõ chữ "đơn hàng" hay "mã đơn"
        if (preg_match('/ORD-[A-Z0-9]+/iu', $message, $m)) {
            return strtoupper($m[0]);
        }

        // 2. Nếu không thấy mã ORD, kiểm tra xem khách có dùng các từ khóa hỏi đơn hàng không
        $hasOrderKeyword = str_contains($text, 'đơn hàng')
            || str_contains($text, 'don hang')
            || str_contains($text, 'mã đơn')
            || str_contains($text, 'ma don')
            || str_contains($text, 'tra cứu đơn')
            || str_contains($text, 'mã vận đơn')
            || preg_match('/\border\b/u', $text) === 1;

        if (!$hasOrderKeyword) {
            return null;
        }

        // 3. Nếu có từ khóa mà khách gõ số ID tĩnh dạng "đơn hàng #5", "mã đơn 7"
        if (preg_match('/(?:đơn hàng|don hang|mã đơn|ma don|tra cứu đơn|mã vận đơn|order)\s*(?:số|so)?\s*#?\s*(\d+)/u', $text, $m)) {
            return 'ID:' . $m[1];
        }
        
        return null;
    }

    private function handleOrderLookup(string $orderIdentifier, ?User $user): array
    {
        if (!$user) {
            return [
                'intent' => 'order_status',
                'reply'  => 'Bạn vui lòng đăng nhập để mình tra cứu đúng đơn hàng của bạn nhé.',
            ];
        }

        $query = Order::with('shipment')->where('user_id', $user->id);

        if (str_starts_with($orderIdentifier, 'ID:')) {
            $query->where('id', (int) substr($orderIdentifier, 3));
        } else {
            // "Mã đơn" khách nhìn thấy trên trang Lịch sử đơn hàng thực chất
            // là cột tracking_number (tên cột gây nhầm lẫn, không phải mã
            // vận đơn thật) — invoice_number là mã khác, dùng nội bộ.
            $query->where('tracking_number', $orderIdentifier);
        }

        $order = $query->first();

        if (!$order) {
            return [
                'intent' => 'order_status',
                'reply'  => "Mình không tìm thấy đơn hàng \"{$orderIdentifier}\" thuộc tài khoản của bạn. "
                          . 'Bạn kiểm tra lại mã đơn (dạng ORD-XXXXXXX) trong mục "Lịch sử đơn hàng" giúp mình nhé.',
            ];
        }

        // Đúng theo enum thật của cột orders.status
        $statusLabels = [
            'pending'       => 'Đang chờ xử lý',
            'processing'    => 'Đang chuẩn bị hàng',
            'ready_to_ship' => 'Sẵn sàng giao cho đơn vị vận chuyển',
            'shipped'       => 'Đang giao hàng',
            'delivered'     => 'Đã giao tới bạn',
            'completed'     => 'Đã hoàn thành',
            'cancelled'     => 'Đã hủy',
        ];

        $statusText = $statusLabels[$order->status] ?? $order->status;
        $carrierTracking = $order->shipment?->tracking_number;

        return [
            'intent' => 'order_status',
            'reply'  => "Đơn hàng {$order->tracking_number} hiện đang ở trạng thái: {$statusText}."
                      . ($carrierTracking ? " Mã vận đơn đơn vị vận chuyển: {$carrierTracking}." : ''),
        ];
    }

    // ==================== CHÍNH SÁCH / FAQ ====================

    private function matchFaq(string $message): ?string
    {
        $text = mb_strtolower($message);

        $faqs = [
            ['keywords' => ['đổi trả', 'doi tra', 'trả hàng', 'tra hang', 'hoàn tiền', 'hoan tien', 'trả lại hàng'], 'reply' =>
                'Electro Shop hỗ trợ đổi trả trong vòng 7 ngày kể từ khi nhận hàng, với điều kiện sản phẩm còn nguyên tem, chưa qua sử dụng.'],
            ['keywords' => ['bảo hành', 'bao hanh', 'warranty'], 'reply' =>
                'Tất cả sản phẩm tại Electro Shop đều được bảo hành chính hãng 12 tháng kể từ ngày mua.'],
            // Đặt TRƯỚC FAQ vận chuyển: câu hỏi kiểu "có ship COD không" chứa
            // cả 'ship' lẫn 'cod', nhưng ý khách hỏi thực chất là về hình
            // thức THANH TOÁN (có hỗ trợ trả tiền khi nhận hàng không), nên
            // phải ưu tiên khớp 'cod' trước khi 'ship' bị FAQ vận chuyển nuốt mất.
            ['keywords' => ['thanh toán', 'thanh toan', 'payment', 'cod', 'trả tiền khi nhận', 'tra tien khi nhan'], 'reply' =>
                'Electro Shop hỗ trợ thanh toán khi nhận hàng (COD) và chuyển khoản ngân hàng qua mã QR.'],
            ['keywords' => ['vận chuyển', 'van chuyen', 'ship', 'giao hàng', 'giao hang', 'phí ship', 'phi ship', 'giao nhận'], 'reply' =>
                'Thời gian giao hàng dự kiến 2-5 ngày tuỳ khu vực. Phí vận chuyển được tính cụ thể ở bước thanh toán dựa theo địa chỉ nhận hàng.'],
        ];

        foreach ($faqs as $faq) {
            foreach ($faq['keywords'] as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $faq['reply'];
                }
            }
        }

        return null;
    }

    // ==================== CONTEXT CHO LLM FALLBACK ====================

    private function buildFallbackContext(): string
    {
        $bestsellers = Product::where('is_active', true)
            ->whereHas('inventoryLogs')
            ->with(['category', 'specifications', 'activeFlashSaleItem'])
            ->orderByDesc('view_count')
            ->take(3)
            ->get();

        if ($bestsellers->isEmpty()) {
            return '';
        }

        $list = collect($bestsellers)->map(function (Product $p) {
            $specs = $p->specifications
                ->map(fn ($s) => "{$s->label}: {$s->value}" . ($s->unit ? " {$s->unit}" : ''))
                ->implode(', ');
            return "- {$p->name} ({$p->category->name}) — " . $this->formatProductPrice($p) . ($specs ? "\n  Thông số: {$specs}" : '');
        })->implode("\n");

        return $list;
    }

    private function buildGlobalContext(?User $user): string
    {
        $context = "THÔNG TIN CHÍNH SÁCH CỬA HÀNG:\n";
        $context .= "- Đổi trả: 7 ngày kể từ khi nhận hàng (sản phẩm còn nguyên tem, chưa qua sử dụng).\n";
        $context .= "- Bảo hành: Chính hãng 12 tháng.\n";
        $context .= "- Thanh toán: Nhận hàng (COD) hoặc Chuyển khoản ngân hàng.\n";
        $context .= "- Vận chuyển: 2-5 ngày tùy khu vực. Phí ship tính ở bước thanh toán.\n\n";

        $vouchers = Voucher::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get();

        if ($vouchers->isNotEmpty()) {
            $context .= "MÃ GIẢM GIÁ ĐANG CÓ:\n";
            foreach ($vouchers as $v) {
                $discountStr = $v->discount_type === 'percent' ? "{$v->discount_value}%" : number_format($v->discount_value, 0, ',', '.') . "đ";
                $context .= "- Mã {$v->code}: Giảm {$discountStr} (Đơn tối thiểu " . number_format($v->min_amount ?? 0, 0, ',', '.') . "đ).\n";
            }
            $context .= "\n";
        }

        if ($user) {
            $context .= "THÔNG TIN KHÁCH HÀNG ĐANG CHAT:\n";
            $context .= "- Tên: {$user->name} (Email: {$user->email})\n";
            
            $orders = Order::where('user_id', $user->id)->orderByDesc('created_at')->take(5)->get();
            if ($orders->isNotEmpty()) {
                $context .= "- Lịch sử mua hàng (5 đơn gần nhất):\n";
                foreach ($orders as $o) {
                    $context .= "  + Mã đơn: {$o->tracking_number} | Tổng tiền: " . number_format($o->total_amount, 0, ',', '.') . "đ | Trạng thái: {$o->status} | Ngày: {$o->created_at->format('d/m/Y')}\n";
                }
            } else {
                $context .= "- Lịch sử mua hàng: Chưa có đơn hàng nào.\n";
            }
        }

        return $context;
    }
}