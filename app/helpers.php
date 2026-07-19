<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('img_url')) {
    /**
     * Trả về URL hiển thị được cho MỌI kiểu giá trị ảnh đang lưu trong DB
     * (products.image, product_images.image_url, product_variants.image...).
     *
     * Hỗ trợ đồng thời 2 dạng:
     * 1) Đường dẫn tương đối do hệ thống tự lưu khi admin upload file lên
     *    storage (VD: "products/abc.jpg") -> ghép thành URL đầy đủ:
     *    asset('storage/products/abc.jpg')
     * 2) URL tuyệt đối được dán trực tiếp từ nơi khác, ví dụ ảnh lấy từ
     *    Google/Bing/CDN ngoài (VD: "https://tse1.explicit.bing.net/...")
     *    -> giữ nguyên như đã nhập, KHÔNG ghép thêm "storage/" vào (vì
     *    ghép vào sẽ tạo ra URL sai/hỏng ảnh).
     *
     * Dùng hàm này thay cho asset('storage/'.$path) ở TẤT CẢ mọi nơi hiển
     * thị ảnh sản phẩm/biến thể/gallery trong cả Blade và API trả về cho
     * Vue admin, để 1 nơi duy nhất quyết định cách dựng URL ảnh.
     */
    function img_url(?string $path, ?string $fallback = null): ?string
    {
        if (empty($path)) {
            return $fallback;
        }

        // Đã là URL đầy đủ (http://, https://) hoặc URL không kèm scheme
        // (//host/...) -> giữ nguyên.
        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        // Trường hợp còn lại: coi là đường dẫn tương đối trong disk "public".
        return asset('storage/' . ltrim($path, '/'));
    }
}

if (! function_exists('is_external_image_url')) {
    /**
     * Kiểm tra 1 giá trị ảnh có phải URL dán từ ngoài hay không (để phân
     * biệt với đường dẫn tương đối do hệ thống tự lưu khi upload file).
     */
    function is_external_image_url(?string $path): bool
    {
        return !empty($path) && (bool) preg_match('#^(https?:)?//#i', $path);
    }
}