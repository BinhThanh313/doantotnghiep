<?php

namespace App\Services\Chatbot;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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

        // Câu hỏi phiếm/ngoài lề hoàn toàn không liên quan mua sắm (thời
        // tiết, giờ giấc, hỏi thăm bot...). CHẶN Ở ĐÂY, TRƯỚC parser sản
        // phẩm và trước LLM fallback — vì buildFallbackContext() luôn kèm
        // sẵn danh sách bestseller làm "phao cứu sinh" cho câu hỏi mua sắm
        // mơ hồ, nên nếu để những câu hỏi kiểu này lọt xuống LLM fallback,
        // model (đặc biệt Groq free tier) có thể vẫn liệt kê sản phẩm ra dù
        // câu hỏi chẳng liên quan gì (VD "trời hôm nay có đẹp không"). Trả
        // lời cố định ở đây đảm bảo hành vi ổn định, không phụ thuộc LLM có
        // tuân thủ prompt hay không.
        if ($this->isOffTopicSmallTalk($message)) {
            return [
                'intent' => 'small_talk',
                'reply'  => 'Haha mình là trợ lý mua sắm nên không rành khoản này lắm 😅. '
                          . 'Bạn cần mình tư vấn sản phẩm gì không, ví dụ laptop, điện thoại, tai nghe...?',
            ];
        }

        // Câu hỏi kiểu so sánh/nối tiếp ("cái nào tốt hơn", "laptop nào tốt
        // nhất"...) PHẢI dựa vào lịch sử hội thoại, không được để parser bắt
        // nhầm thành 1 lượt tìm kiếm sản phẩm mới chỉ vì có chứa tên danh
        // mục (VD: "laptop nào tốt hơn" chứa chữ "laptop"). Ưu tiên kiểm tra
        // trước khi chạy parser.
        if ($this->isFollowUpComparison($message) && !empty($history)) {
            $context = $this->buildContextFromRecentProducts($history);
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

        // Không khớp intent nào rõ ràng -> fallback LLM, kèm ngữ cảnh vài
        // sản phẩm bán chạy + lịch sử hội thoại gần nhất, để LLM hiểu được
        // câu hỏi nối tiếp (VD: "cái nào tốt hơn" nhắc tới kết quả tìm kiếm
        // ngay phía trên) thay vì trả lời khống hoặc không hiểu ngữ cảnh.
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
     */
    private function buildContextFromRecentProducts(array $history): string
    {
        $recentBotText = collect($history)
            ->filter(fn ($m) => $m['sender'] === 'bot')
            ->take(-3) // 3 tin nhắn bot gần nhất
            ->pluck('message')
            ->implode(' ');

        // Chỉ cần so khớp trong phạm vi ~58 sản phẩm active, chi phí không đáng kể
        $allNames = Product::where('is_active', true)->pluck('name');
        $matchedNames = $recentBotText !== ''
            ? $allNames->filter(fn ($name) => str_contains($recentBotText, $name))
            : collect();

        if ($matchedNames->isEmpty()) {
            // Không xác định được sản phẩm CỤ THỂ nào đang bàn tới — có thể
            // vì câu hỏi thực ra là 1 câu hỏi MỚI (VD "sản phẩm nào đang
            // hot") bị nhận nhầm là câu hỏi tiếp nối, chứ không phải khách
            // thực sự đang so sánh 1 sản phẩm cũ. Thay vì để LLM bí và chỉ
            // biết nói "không có thông tin", đưa luôn danh sách bestseller
            // thật làm phao cứu sinh để LLM vẫn tư vấn được — vẫn giữ chặt
            // yêu cầu không được tự bịa sản phẩm ngoài danh sách.
            $note = 'KHÔNG có sản phẩm cụ thể nào đang được bàn tới trong lịch sử hội thoại gần đây. '
                . 'Nếu đây là câu hỏi MỚI (không thực sự so sánh/tiếp nối 1 sản phẩm cũ), hãy dùng danh '
                . 'sách sản phẩm nổi bật dưới đây để tư vấn — TUYỆT ĐỐI không tự bịa sản phẩm ngoài danh '
                . 'sách này. Nếu câu hỏi thực sự cần biết rõ khách đang nói về sản phẩm nào, hãy hỏi lại khách.';
            return $note . "\n\nDanh sách sản phẩm nổi bật:\n" . $this->buildFallbackContext()
                 . "\n\n" . $this->formatHistory($history);
        }

        $products = Product::with(['category', 'specifications', 'activeFlashSaleItem'])
            ->whereIn('name', $matchedNames)
            ->get();

        $productContext = $this->buildProductContextWithSpecs($products);

        return "Đây là các sản phẩm ĐANG được bàn tới trong cuộc hội thoại này (chỉ so sánh/nhận xét trong phạm vi các sản phẩm này, không nhắc sản phẩm khác):\n{$productContext}\n\n"
             . $this->formatHistory($history);
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
     * Nhận diện các câu hỏi phiếm/xã giao KHÔNG liên quan gì tới mua sắm,
     * sản phẩm, đơn hàng hay chính sách shop — điển hình là hỏi thăm thời
     * tiết ("trời hôm nay có đẹp không"), giờ giấc, hoặc hỏi thăm chính bot.
     * Đây là danh sách các chủ đề phổ biến nhất khách hay hỏi lạc đề, KHÔNG
     * nhằm bao phủ mọi câu ngoài lề có thể có (những câu hiếm gặp hơn vẫn sẽ
     * rơi xuống LLM fallback, đã được dặn dò không tự ý liệt kê sản phẩm).
     */
    private function isOffTopicSmallTalk(string $message): bool
    {
        $text = mb_strtolower($message);

        $patterns = [
            // Thời tiết
            'thời tiết', 'thoi tiet', 'trời hôm nay', 'troi hom nay',
            'trời có đẹp', 'trời đẹp không', 'troi dep khong', 'trời mưa',
            'troi mua', 'trời nắng', 'troi nang', 'dự báo thời tiết',
            // Giờ giấc / ngày tháng phiếm (không phải tra cứu đơn hàng)
            'mấy giờ rồi', 'may gio roi', 'bây giờ là mấy giờ', 'hôm nay ngày mấy',
            // Hỏi thăm chính bot (không phải hỏi về sản phẩm)
            'bạn khỏe không', 'ban khoe khong', 'bạn ăn cơm chưa',
            'bạn là ai', 'bạn tên gì', 'bạn có phải là người thật không',
            'bạn có người yêu không',
        ];

        foreach ($patterns as $p) {
            if (str_contains($text, $p)) {
                return true;
            }
        }
        return false;
    }

    // ==================== TÌM SẢN PHẨM ====================

    private array $subjectiveQualifiers = [
        'phù hợp cho', 'phù hợp với', 'dành cho', 'tốt cho', 'thích hợp cho',
        'thích hợp với', 'nên chọn', 'nên mua', 'phù hợp nhất',
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
                    // Chỉ '>=' được sinh ra từ parser -> an toàn để nối chuỗi SQL
                    $q->whereRaw(
                        "CAST(REGEXP_REPLACE(value, '[^0-9]', '') AS UNSIGNED) >= ?",
                        [$spec['value']]
                    );
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
        return $products->map(function (Product $p) {
            $specs = $p->specifications
                ->take(6)
                ->map(fn ($s) => "{$s->label}: {$s->value}" . ($s->unit ? " {$s->unit}" : ''))
                ->implode(', ');

            return "- {$p->name} — " . $this->formatProductPrice($p)
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
            ['keywords' => ['vận chuyển', 'van chuyen', 'ship', 'giao hàng', 'giao hang', 'phí ship', 'phi ship', 'giao nhận'], 'reply' =>
                'Thời gian giao hàng dự kiến 2-5 ngày tuỳ khu vực. Phí vận chuyển được tính cụ thể ở bước thanh toán dựa theo địa chỉ nhận hàng.'],
            ['keywords' => ['thanh toán', 'thanh toan', 'payment'], 'reply' =>
                'Electro Shop hỗ trợ thanh toán khi nhận hàng (COD) và chuyển khoản ngân hàng qua mã QR.'],
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
            ->where('is_bestseller', true)
            ->limit(8)
            ->get(['id', 'name', 'price', 'category_id'])
            ->load(['category', 'activeFlashSaleItem']);

        if ($bestsellers->isEmpty()) {
            return 'Không có dữ liệu sản phẩm nổi bật nào.';
        }

        return $bestsellers->map(function (Product $p) {
            return "- {$p->name} ({$p->category->name}) — " . $this->formatProductPrice($p);
        })->implode("\n");
    }
}