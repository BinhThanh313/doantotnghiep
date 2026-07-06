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

        return [
            'category'  => $this->detectCategory($text),
            'brand'     => $this->detectBrand($text),
            'price_min' => $this->detectPriceMin($text),
            'price_max' => $this->detectPriceMax($text),
            'specs'     => array_merge(
                $this->detectCpu($text),
                $this->detectRam($text),
                $this->detectStorage($text),
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
     * "trên 10 triệu", "khoảng 15 triệu". Số tiền mặc định hiểu là ĐƠN VỊ TRIỆU.
     */
    private function detectPriceMin(string $text): ?int
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|đến|toi|tới)\s*(\d+(?:[.,]\d+)?)\s*(?:tr|triệu)/u', $text, $m)) {
            return (int) round(((float) str_replace(',', '.', $m[1])) * 1_000_000);
        }
        if (preg_match('/trên\s*(\d+(?:[.,]\d+)?)\s*(?:tr|triệu)/u', $text, $m)) {
            return (int) round(((float) str_replace(',', '.', $m[1])) * 1_000_000);
        }
        return null;
    }

    private function detectPriceMax(string $text): ?int
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|đến|toi|tới)\s*(\d+(?:[.,]\d+)?)\s*(?:tr|triệu)/u', $text, $m)) {
            return (int) round(((float) str_replace(',', '.', $m[2])) * 1_000_000);
        }
        if (preg_match('/(?:dưới|duoi|tối đa|toi da)\s*(\d+(?:[.,]\d+)?)\s*(?:tr|triệu)/u', $text, $m)) {
            return (int) round(((float) str_replace(',', '.', $m[1])) * 1_000_000);
        }
        if (preg_match('/khoảng\s*(\d+(?:[.,]\d+)?)\s*(?:tr|triệu)/u', $text, $m)) {
            // "khoảng X triệu" -> hiểu là +-15%
            $mid = ((float) str_replace(',', '.', $m[1])) * 1_000_000;
            return (int) round($mid * 1.15);
        }
        return null;
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
        if (preg_match('/ram\D{0,15}?(\d+)\s*(?:gb)?/u', $text, $m)) {
            return [['label' => 'RAM', 'operator' => '>=', 'value' => (int) $m[1]]];
        }
        return [];
    }

    /** "1tb", "512gb ổ cứng", "dung lượng 256gb" -> tối thiểu (>=)
     *  Marker 'STORAGE' vì nhãn thật trong DB là "Ổ cứng" (laptop) hoặc
     *  "Bộ nhớ trong" (điện thoại/tablet) — ChatbotResponseService sẽ tự
     *  OR cả 2 nhãn này khi gặp marker STORAGE. */
    private function detectStorage(string $text): array
    {
        if (preg_match('/(\d+)\s*tb\b/u', $text, $m)) {
            return [['label' => 'STORAGE', 'operator' => '>=', 'value' => (int) $m[1] * 1000]];
        }
        if (preg_match('/(?:ổ cứng|dung lượng|storage|bộ nhớ trong)\D{0,15}?(\d+)\s*gb\b/u', $text, $m)) {
            return [['label' => 'STORAGE', 'operator' => '>=', 'value' => (int) $m[1]]];
        }
        return [];
    }
}