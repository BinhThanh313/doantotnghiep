<?php

namespace App\Services\Chatbot;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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
        // nhất"...) PHẢI dựa vào lịch sử hội thoại, không được để parser bắt
        // nhầm thành 1 lượt tìm kiếm sản phẩm mới chỉ vì có chứa tên danh
        // mục (VD: "laptop nào tốt hơn" chứa chữ "laptop"). Ưu tiên kiểm tra
        // trước khi chạy parser.
        if ($this->isFollowUpComparison($message)) {
            $products = $this->findRecentlyMentionedProducts($history, $message);

            Log::info('[chatbot-debug] follow_up_comparison', [
                'message'        => $message,
                'history_count'  => count($history),
                'history'        => $history,
                'products_found' => $products->pluck('name')->all(),
            ]);

            if ($products->isEmpty()) {
                // KHÔNG xác định được sản phẩm cụ thể nào đang được bàn tới
                // (có thể vì tin nhắn bot trước đó do LLM viết lại tên sản
                // phẩm không khớp 100% ký tự với DB, hoặc đây thực ra là câu
                // hỏi MỚI bị nhận nhầm là câu hỏi tiếp nối). TRẢ LỜI CỐ ĐỊNH
                // hỏi lại khách ở đây, KHÔNG đẩy quyết định "liệt kê bestseller
                // hay hỏi lại" cho LLM — vì thực tế đã cho thấy LLM (Groq free
                // tier) không đáng tin cậy ở việc này, dễ lặp lại đúng bug cũ:
                // cứ có sẵn context sản phẩm là liệt kê ra, dù câu hỏi ("cái
                // này có đáng mua không") không hề yêu cầu liệt kê danh sách.
                return [
                    'intent' => 'clarify_product',
                    'reply'  => 'Bạn đang hỏi về sản phẩm nào vậy? Bạn nhắc lại tên sản phẩm giúp mình để tư vấn chính xác hơn nhé.',
                ];
            }

            $context = $this->buildProductContextWithSpecs($products)
                     . "\n\n" . $this->formatHistory($history);
            return [
                'intent' => 'llm_fallback',
                'reply'  => $this->llm->reply($message, $context),
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

        // Tới đây nghĩa là KHÔNG có category/brand/giá/spec (đã bị
        // parser->isProductSearch() loại), không phải tra đơn hàng, không
        // khớp FAQ. Đây chính là điểm hay rò rỉ: nếu cứ mặc định coi là
        // "câu hỏi mua sắm mơ hồ" và đưa nguyên context bestseller cho LLM,
        // model (đặc biệt Groq free tier) có thể liệt kê sản phẩm ra dù câu
        // hỏi chẳng liên quan gì tới mua sắm (VD "trời hôm nay có đẹp
        // không"). Dùng WHITELIST — chỉ cho đi tiếp (kèm context sản phẩm)
        // khi có tín hiệu THỰC SỰ liên quan mua sắm; mặc định còn lại coi là
        // ngoài lề và chặn ngay tại đây, không gọi LLM. An toàn hơn hẳn so
        // với việc liệt kê trước từng câu ngoài lề có thể gặp (blacklist),
        // vì không thể lường hết mọi câu hỏi phiếm mà khách/giám khảo có
        // thể hỏi.
        if (!$this->hasShoppingSignal($message)) {
            return [
                'intent' => 'small_talk',
                'reply'  => 'Haha mình là trợ lý mua sắm nên không rành khoản này lắm 😅. '
                          . 'Bạn cần mình tư vấn sản phẩm gì không, ví dụ laptop, điện thoại, tai nghe...?',
            ];
        }

        // Không khớp intent rõ ràng nhưng CÓ tín hiệu liên quan mua sắm (VD
        // "có gì hot không", "tư vấn giúp mình") -> fallback LLM, kèm ngữ
        // cảnh vài sản phẩm bán chạy + lịch sử hội thoại gần nhất, để LLM
        // hiểu được câu hỏi nối tiếp (VD: "cái nào tốt hơn" nhắc tới kết quả
        // tìm kiếm ngay phía trên) thay vì trả lời khống hoặc không hiểu
        // ngữ cảnh.
        $context = $this->buildFallbackContext() . "\n\n" . $this->formatHistory($history);
        return [
            'intent' => 'llm_fallback',
            'reply'  => $this->llm->reply($message, $context),
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
        if (empty($history)) {
            return 'Không có lịch sử hội thoại trước đó.';
        }

        $lines = collect($history)->map(function ($m) {
            $who = $m['sender'] === 'user' ? 'Khách' : 'Bot';
            return "{$who}: {$m['message']}";
        })->implode("\n");

        return "Lịch sử hội thoại gần nhất (từ cũ đến mới, câu cuối là câu khách vừa hỏi):\n{$lines}";
    }

    // ==================== CHÀO HỎI ====================

    private function isFollowUpComparison(string $message): bool
    {
        $text = mb_strtolower($message);
        $patterns = [
            // So sánh / xếp hạng giữa nhiều sản phẩm. LƯU Ý: KHÔNG để "cái
            // nào"/"con nào"/"sản phẩm nào" đứng một mình trong danh sách —
            // các case cần bắt như "cái nào tốt hơn", "laptop nào tốt nhất"
            // đã được phủ sẵn bởi 'tốt hơn'/'tốt nhất' bên dưới; để đứng một
            // mình sẽ khiến câu hỏi MỚI hoàn toàn (VD "sản phẩm nào đang
            // hot") bị hiểu nhầm thành đang so sánh tiếp sản phẩm cũ.
            'tốt hơn', 'tốt nhất', 'ngon hơn',
            'so sánh', 'đáng mua hơn', 'nên chọn cái',
            'so với nhau', 'so với cái', 'giữa hai', 'giữa 2', 'hai cái này',
            'cái này với cái kia', 'nên mua cái nào', 'khác nhau thế nào',
            'khác nhau chỗ nào', 'khác gì nhau', 'ưu nhược điểm', 'nên lấy cái',
            // Tham chiếu đại từ tới sản phẩm vừa nhắc ("cái đó", "sản phẩm
            // này"...) — KHÔNG cần yếu tố so sánh, chỉ cần đang hỏi tiếp về
            // 1 sản phẩm đã nêu trước đó thay vì mở 1 chủ đề mới.
            'cái đó', 'cái này', 'con đó', 'con này', 'sản phẩm đó', 'sản phẩm này',
            'hàng đó', 'hàng này', 'món đó', 'món này', 'em đó', 'em này',
            // Câu hỏi đánh giá 1 sản phẩm (không kèm từ so sánh "hơn") —
            // thường đi ngay sau khi bot vừa liệt kê/nhắc tới 1 sản phẩm
            'đáng mua không', 'có đáng mua', 'có nên mua', 'có tốt không',
            'có ổn không', 'có đáng không', 'mua được không',
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

    /**
     * Câu hỏi có TÍN HIỆU liên quan mua sắm/sản phẩm/đơn hàng/chính sách shop
     * hay không — cổng WHITELIST trước khi 1 câu hỏi "mơ hồ" (không khớp
     * parser/order/FAQ) được đưa xuống LLM fallback kèm context bestseller.
     * Chỉ cần khớp 1 tín hiệu là đủ cho qua; parser/order/FAQ ở các bước
     * trước đã lo phần chính xác rồi, hàm này chỉ cần lỏng tay hơn 1 chút để
     * không chặn nhầm câu hỏi mua sắm nói vòng vo (VD "có gì hot không").
     */
    private function hasShoppingSignal(string $message): bool
    {
        $text = mb_strtolower($message);

        $genericSignals = [
            'mua', 'bán', 'giá', 'tư vấn', 'gợi ý', 'đề xuất', 'recommend',
            'sản phẩm', 'hàng gì', 'shop', 'cửa hàng', 'khuyến mãi', 'giảm giá',
            'sale', 'flash sale', 'nổi bật', 'hot', 'bán chạy', 'xu hướng',
            'trending', 'đơn hàng', 'don hang', 'giao hàng', 'giao hang',
            'vận chuyển', 'van chuyen', 'tra cứu', 'chính sách', 'chinh sach',
            'bảo hành', 'bao hanh', 'đổi trả', 'doi tra', 'thanh toán', 'thanh toan',
            'hotline', 'combo', 'thông số', 'thong so', 'cấu hình', 'cau hinh',
            'cái đầu', 'cái thứ', 'cái này', 'cái kia', 'cái đó', 'mấy cái', 'những cái',
            'pin', 'màn hình', 'camera', 'chụp', 'sạc', 'dung lượng',
        ];

        foreach ($genericSignals as $s) {
            if (str_contains($text, $s)) {
                return true;
            }
        }

        foreach ($this->parser->knownProductKeywords() as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

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

    /** Ngữ cảnh đầy đủ (kèm vài thông số nổi bật) để LLM xếp hạng/diễn giải, không tự bịa sản phẩm ngoài danh sách này */
    private function buildProductContextWithSpecs($products): string
    {
        // Đánh số thứ tự (1., 2., 3...) thay vì gạch đầu dòng: $products đã
        // được sắp đúng theo thứ tự xuất hiện trong hội thoại (xem
        // findRecentlyMentionedProducts), nên đánh số giúp LLM trả lời được
        // các câu hỏi kiểu "cái đầu tiên/thứ hai/cuối cùng có đáng mua không".
        return collect($products)->values()->map(function (Product $p, int $i) {
            $specs = $p->specifications
                ->map(fn ($s) => "{$s->label}: {$s->value}" . ($s->unit ? " {$s->unit}" : ''))
                ->implode(', ');

            return ($i + 1) . ". {$p->name} — " . $this->formatProductPrice($p)
                 . ($p->category ? " ({$p->category->name})" : '')
                 . ($specs ? "\n  Thông số: {$specs}" : '');
        })->implode("\n");
    }

    // ==================== TRA CỨU ĐƠN HÀNG ====================

    private function extractOrderId(string $message): ?string
    {
        $text = mb_strtolower($message);

        // Mở rộng thêm vài cách hỏi phổ biến khác ngoài "đơn hàng"/"don
        // hang" (mã đơn, tra cứu đơn, mã vận đơn, "order" tiếng Anh). Dùng
        // \b cho "order" để tránh khớp nhầm vào giữa từ khác (VD "border").
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

        // Ưu tiên mã đơn thật của hệ thống, dạng "ORD-824C3233"
        if (preg_match('/ORD-[A-Z0-9]+/iu', $message, $m)) {
            return strtoupper($m[0]);
        }

        // Fallback: khách gõ số ID NGAY SAU từ khóa đơn hàng (VD "đơn hàng #5",
        // "đơn hàng số 12", "mã đơn 7") — CHỈ bắt số nằm SÁT từ khóa (cho phép
        // xen "số"/"so"/"#"/khoảng trắng ở giữa), để tránh tra nhầm một con số
        // bất kỳ xuất hiện ở đâu đó xa trong câu (VD "đơn hàng của tôi có 3
        // sản phẩm" không được hiểu là tra đơn ID 3).
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
}