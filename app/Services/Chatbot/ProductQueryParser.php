<?php

namespace App\Services\Chatbot;

/**
 * Tách 1 câu hỏi tiếng Việt tự nhiên (VD: "tìm laptop giá 15 đến 20 triệu
 * core i7 ram tối thiểu 12") thành bộ lọc có cấu trúc để query trực tiếp
 * vào DB — đây là lớp "rule-based" chính xác, không phụ thuộc LLM.
 *
 * Kết quả trả về:
 * [
 *   'category'  => 'Laptop' | null,
 *   'price_min' => 15000000 | null,
 *   'price_max' => 20000000 | null,
 *   'specs'     => [
 *      ['label' => 'RAM', 'operator' => '>=', 'value' => 12],
 *      ['label' => 'CPU', 'operator' => 'contains', 'value' => 'i7'],
 *   ],
 * ]
 * Lưu ý: 'STORAGE' là marker đặc biệt cho dung lượng ổ cứng/bộ nhớ trong,
 * vì nhãn thật trong DB khác nhau theo danh mục ("Ổ cứng" / "Bộ nhớ trong").
 */
class ProductQueryParser
{
    /** Từ khoá danh mục -> tên category thật trong DB */
    private array $categoryKeywords = [
        'laptop'       => 'Laptop', 'macbook' => 'Laptop',
        'điện thoại'   => 'Điện thoại', 'smartphone' => 'Điện thoại', 'dien thoai' => 'Điện thoại',
        'máy tính bảng'=> 'Máy tính bảng', 'ipad' => 'Máy tính bảng', 'tablet' => 'Máy tính bảng',
        'đồng hồ'      => 'Đồng hồ thông minh', 'smartwatch' => 'Đồng hồ thông minh',
        'tai nghe'     => 'Tai nghe', 'headphone' => 'Tai nghe',
        'action cam'   => 'Camera', 'camera hành trình' => 'Camera',
        'máy ảnh'      => 'Máy ảnh',
        'tivi'         => 'Tivi', 'tv' => 'Tivi',
        'loa'          => 'Loa bluetooth',
        'phụ kiện'     => 'Phụ kiện',
    ];

    /** Từ khoá thông số -> nhãn thông số trong bảng product_specifications */
    private array $specLabelKeywords = [
        'ram'        => 'RAM',
        'ổ cứng'     => 'lưu trữ', 'dung lượng lưu trữ' => 'lưu trữ', 'bộ nhớ trong' => 'lưu trữ', 'storage' => 'lưu trữ',
        'pin'        => 'Pin',
        'màn hình'   => 'màn hình',
    ];

    /** Từ khoá thương hiệu -> chuỗi cần khớp trong TÊN sản phẩm */
    private array $brandKeywords = [
        'apple' => 'iPhone|iPad|MacBook|Apple Watch', // Apple không có tên hãng trong tên sp, ánh xạ qua dòng sản phẩm
        'iphone' => 'iPhone',
        'ipad' => 'iPad',
        'macbook' => 'MacBook',
        'samsung' => 'Samsung',
        'xiaomi' => 'Xiaomi',
        'oppo' => 'OPPO',
        'vivo' => 'Vivo',
        'realme' => 'Realme',
        'sony' => 'Sony',
        'lg' => 'LG',
        'asus' => 'ASUS',
        'dell' => 'Dell',
        'hp' => 'HP',
        'lenovo' => 'Lenovo',
        'jbl' => 'JBL',
        'bose' => 'Bose',
        'marshall' => 'Marshall',
        'canon' => 'Canon',
        'nikon' => 'Nikon',
        'fujifilm' => 'Fujifilm',
        'gopro' => 'GoPro',
        'dji' => 'DJI',
        'insta360' => 'Insta360',
        'garmin' => 'Garmin',
        'tcl' => 'TCL',
        'panasonic' => 'Panasonic',
    ];

    public function parse(string $text): array
    {
        $text = mb_strtolower(trim($text));

        $ramMatches = $this->detectRam($text);
        $ramValue = $ramMatches[0]['value'] ?? null;

        return [
            'category'  => $this->detectCategory($text),
            'brand'     => $this->detectBrand($text),
            'price_min' => $this->detectPriceMin($text),
            'price_max' => $this->detectPriceMax($text),
            'specs'     => array_merge(
                $this->detectCpu($text),
                $ramMatches,
                $this->detectStorage($text, $ramValue),
            ),
        ];
    }

    /** Câu hỏi có được coi là "tìm sản phẩm" không (đủ tín hiệu để query DB) */
    public function isProductSearch(array $filters): bool
    {
        return $filters['category'] !== null
            || $filters['brand'] !== null
            || $filters['price_min'] !== null
            || $filters['price_max'] !== null
            || !empty($filters['specs']);
    }

    /**
     * Trả về mẫu LIKE (có thể là "A|B|C" cho nhiều tên gộp của cùng 1 hãng,
     * VD Apple không xuất hiện trong tên sản phẩm mà là iPhone/iPad/MacBook).
     */
    private function detectBrand(string $text): ?string
    {
        foreach ($this->brandKeywords as $keyword => $namePattern) {
            if (str_contains($text, $keyword)) {
                return $namePattern;
            }
        }
        return null;
    }

    private function detectCategory(string $text): ?string
    {
        foreach ($this->categoryKeywords as $keyword => $category) {
            if (str_contains($text, $keyword)) {
                return $category;
            }
        }
        return null;
    }

    /**
     * Nhận diện khoảng giá dạng: "15 đến 20 triệu", "15-20tr", "dưới 20 triệu",
     * "trên 10 triệu", "khoảng 15 triệu", "dưới 500k", "trên 2 tỷ", "dưới
     * 500000đ". Đơn vị được nhận diện tường minh (tr/triệu/k/nghìn/tỷ) thay
     * vì mặc định luôn là triệu, tránh hiểu sai giá phụ kiện/tai nghe rẻ.
     */
    private const PRICE_UNIT_PATTERN = '(?:tr|triệu|k|nghìn|tỷ)';

    private function detectPriceMin(string $text): ?int
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|đến|toi|tới)\s*(\d+(?:[.,]\d+)?)\s*' . self::PRICE_UNIT_PATTERN . '/u', $text, $m)) {
            return $this->toVnd($m[1], $this->extractUnit($m[0]));
        }
        if (preg_match('/trên\s*(\d+(?:[.,]\d+)?)\s*(' . self::PRICE_UNIT_PATTERN . ')/u', $text, $m)) {
            return $this->toVnd($m[1], $m[2]);
        }
        return null;
    }

    private function detectPriceMax(string $text): ?int
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|đến|toi|tới)\s*(\d+(?:[.,]\d+)?)\s*(' . self::PRICE_UNIT_PATTERN . ')/u', $text, $m)) {
            return $this->toVnd($m[2], $m[3]);
        }
        if (preg_match('/(?:dưới|duoi|tối đa|toi da)\s*(\d+(?:[.,]\d+)?)\s*(' . self::PRICE_UNIT_PATTERN . ')/u', $text, $m)) {
            return $this->toVnd($m[1], $m[2]);
        }
        if (preg_match('/khoảng\s*(\d+(?:[.,]\d+)?)\s*(' . self::PRICE_UNIT_PATTERN . ')/u', $text, $m)) {
            // "khoảng X ..." -> hiểu là +-15%
            $mid = $this->toVnd($m[1], $m[2]);
            return (int) round($mid * 1.15);
        }
        // Số tiền tuyệt đối kèm "đ"/"vnd"/"đồng", VD "dưới 500000đ", "tối đa
        // 15.000.000 vnd" — không quy về triệu, giữ nguyên giá trị thật.
        if (preg_match('/(?:dưới|duoi|tối đa|toi da)\s*(\d[\d.,]*)\s*(?:đ\b|vnd\b|đồng)/u', $text, $m)) {
            return (int) preg_replace('/[.,]/', '', $m[1]);
        }
        return null;
    }

    /** Trích đơn vị giá (tr/triệu/k/nghìn/tỷ) từ đoạn khớp khoảng giá "X-Y đơn_vị" */
    private function extractUnit(string $matched): string
    {
        preg_match('/(' . self::PRICE_UNIT_PATTERN . ')$/u', trim($matched), $m);
        return $m[1] ?? 'triệu';
    }

    /** Quy đổi số + đơn vị tiếng Việt về VNĐ */
    private function toVnd(string $numStr, string $unit): int
    {
        $num = (float) str_replace(',', '.', $numStr);
        $multiplier = match (mb_strtolower($unit)) {
            'tr', 'triệu' => 1_000_000,
            'k', 'nghìn'  => 1_000,
            'tỷ'          => 1_000_000_000,
            default        => 1_000_000,
        };
        return (int) round($num * $multiplier);
    }

    /** Nhận diện chip: i3/i5/i7/i9, ryzen 3/5/7/9, m1/m2/m3, snapdragon, apple a-series */
    private function detectCpu(string $text): array
    {
        if (preg_match('/\bi([3579])\b/u', $text, $m)) {
            return [['label' => 'CPU', 'operator' => 'contains', 'value' => 'i' . $m[1]]];
        }
        if (preg_match('/ryzen\s*([3579])/u', $text, $m)) {
            return [['label' => 'CPU', 'operator' => 'contains', 'value' => 'Ryzen ' . $m[1]]];
        }
        if (preg_match('/\bm([123])\b/u', $text, $m)) {
            return [['label' => 'Chipset', 'operator' => 'contains', 'value' => 'M' . $m[1]]];
        }
        return [];
    }

    /**
     * "ram tối thiểu 12", "ram từ 8gb", "ram 16", "ram trên 8" -> mặc định
     * hiểu là ">=" (tối thiểu) vì đây là nhu cầu phổ biến nhất khi hỏi mua.
     */
    private function detectRam(string $text): array
    {
        // Từ khoá "ram" đứng TRƯỚC số (cách nói phổ biến nhất)
        if (preg_match('/ram\D{0,15}?(\d+)\s*gb?/u', $text, $m)) {
            return [['label' => 'RAM', 'operator' => '>=', 'value' => (int) $m[1]]];
        }
        // Số đứng TRƯỚC từ khoá "ram", VD "16gb ram", "8 gb ram" — bổ sung
        // vì khách hàng cũng hay liệt kê thông số theo chiều này.
        if (preg_match('/(\d+)\s*gb\b\D{0,10}?ram\b/u', $text, $m)) {
            return [['label' => 'RAM', 'operator' => '>=', 'value' => (int) $m[1]]];
        }
        return [];
    }

    /** "1tb", "512gb ổ cứng", "dung lượng 256gb" -> tối thiểu (>=)
     *  Marker 'STORAGE' vì nhãn thật trong DB là "Ổ cứng" (laptop) hoặc
     *  "Bộ nhớ trong" (điện thoại/tablet) — ChatbotResponseService sẽ tự
     *  OR cả 2 nhãn này khi gặp marker STORAGE.
     *
     * @param int|null $ramValue Giá trị RAM đã nhận diện được ở detectRam()
     *  (nếu có), để loại trừ khỏi nhánh đoán "số + gb đứng riêng lẻ" bên
     *  dưới, tránh nhầm "ram 8gb" thành dung lượng lưu trữ 8.
     */
    private function detectStorage(string $text, ?int $ramValue = null): array
    {
        if (preg_match('/(\d+)\s*tb\b/u', $text, $m)) {
            return [['label' => 'STORAGE', 'operator' => '>=', 'value' => (int) $m[1] * 1000]];
        }
        // Từ khoá đứng TRƯỚC số: "ổ cứng 512gb", "dung lượng 256 gb"
        if (preg_match('/(?:ổ cứng|dung lượng|storage|bộ nhớ trong)\D{0,15}?(\d+)\s*gb\b/u', $text, $m)) {
            return [['label' => 'STORAGE', 'operator' => '>=', 'value' => (int) $m[1]]];
        }
        // Từ khoá đứng SAU số: "512gb ổ cứng", "256gb bộ nhớ trong" — bổ
        // sung vì đây cũng là cách nói phổ biến.
        if (preg_match('/(\d+)\s*gb\b\D{0,15}?(?:ổ cứng|dung lượng|storage|bộ nhớ trong)/u', $text, $m)) {
            return [['label' => 'STORAGE', 'operator' => '>=', 'value' => (int) $m[1]]];
        }
        // "iphone 128gb", "laptop 512gb" — số + "gb" đứng riêng lẻ, không
        // kèm từ khoá nào cả. Đây là cách nói phổ biến NHẤT khi hỏi mua
        // điện thoại/laptop theo dung lượng, nên mặc định hiểu là lưu trữ —
        // trừ khi số này đã được detectRam() nhận là RAM hoặc đi kèm chữ
        // "ram" ở gần đó.
        if (preg_match('/(\d+)\s*gb\b/u', $text, $m)) {
            $value = (int) $m[1];
            $looksLikeRam = $value === $ramValue
                || preg_match('/ram\D{0,10}?' . $value . '\s*gb?\b/u', $text) === 1
                || preg_match('/\b' . $value . '\s*gb\b\D{0,10}?ram\b/u', $text) === 1;

            if (!$looksLikeRam) {
                return [['label' => 'STORAGE', 'operator' => '>=', 'value' => $value]];
            }
        }
        return [];
    }
}