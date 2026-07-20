<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Thêm các HTTP header bảo mật cơ bản (phòng thủ theo chiều sâu) cho MỌI
 * response — cả storefront (Blade) lẫn admin SPA (Vue) đều đi qua chung
 * middleware này vì cả hai đều được serve từ cùng 1 Laravel app.
 *
 * Các header này KHÔNG thay thế các lớp bảo vệ khác (auth, validate,
 * CSRF...) mà chỉ giảm thiệt hại nếu trình duyệt/bên thứ 3 cố khai thác
 * theo hướng clickjacking, MIME-sniffing, rò rỉ Referrer, v.v.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Không cho phép nhúng trang trong <iframe> ở domain khác → chống clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Chặn trình duyệt tự đoán loại file (MIME-sniffing) → giảm rủi ro
        // thực thi nhầm file upload (VD: ảnh chứa mã HTML/JS) như script
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Hạn chế thông tin URL bị lộ ra khi người dùng click link ra ngoài
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Tắt các API trình duyệt nhạy cảm không dùng tới trong dự án này
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}