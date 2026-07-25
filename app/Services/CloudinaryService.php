<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service trung tâm xử lý upload/delete ảnh qua Cloudinary.
 *
 * - Khi biến env CLOUDINARY_CLOUD_NAME đã set → upload lên Cloudinary,
 *   trả về URL đầy đủ (https://res.cloudinary.com/...).
 * - Khi chưa set (dev local) → fallback về local disk "public" như cũ.
 *
 * Dùng class này ở TẤT CẢ controllers thay cho $file->store(..., 'public')
 * và Storage::disk('public')->delete(...).
 */
class CloudinaryService
{
    /**
     * Trả về instance Cloudinary đã cấu hình.
     */
    private static function client(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key'    => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * Kiểm tra Cloudinary đã được cấu hình chưa.
     */
    public static function isConfigured(): bool
    {
        return !empty(config('services.cloudinary.cloud_name'))
            && !empty(config('services.cloudinary.api_key'))
            && !empty(config('services.cloudinary.api_secret'));
    }

    /**
     * Upload file từ UploadedFile (khi admin/user upload qua form).
     *
     * @param  UploadedFile $file   File ảnh/video từ request
     * @param  string       $folder Thư mục trên Cloudinary (VD: 'products', 'reviews')
     * @return string               URL đầy đủ (Cloudinary) hoặc relative path (local)
     */
    public static function upload(UploadedFile $file, string $folder = 'products'): string
    {
        // Fallback local khi chưa cấu hình Cloudinary (dev local)
        if (!static::isConfigured()) {
            return $file->store($folder, 'public');
        }

        try {
            $resourceType = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';

            $result = static::client()->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'folder'        => $folder,
                    'resource_type' => $resourceType,
                    'overwrite'     => false,
                    'unique_filename' => true,
                ]
            );

            return $result['secure_url'];
        } catch (\Throwable $e) {
            Log::error('Cloudinary upload failed, falling back to local', [
                'error'  => $e->getMessage(),
                'folder' => $folder,
            ]);
            // Fallback local nếu Cloudinary lỗi
            return $file->store($folder, 'public');
        }
    }

    /**
     * Upload nội dung binary (dùng cho import ảnh từ URL bên ngoài).
     *
     * @param  string $content  Nội dung binary của ảnh
     * @param  string $filename Tên file (dùng làm public_id)
     * @param  string $folder   Thư mục trên Cloudinary
     * @return string           URL đầy đủ (Cloudinary) hoặc relative path (local)
     */
    public static function uploadFromContent(string $content, string $filename, string $folder = 'products'): string
    {
        // Fallback local
        if (!static::isConfigured()) {
            $path = "{$folder}/{$filename}";
            Storage::disk('public')->put($path, $content);
            return $path;
        }

        try {
            // Tạo file tạm để upload
            $tmpFile = tempnam(sys_get_temp_dir(), 'cld_');
            file_put_contents($tmpFile, $content);

            $publicId = pathinfo($filename, PATHINFO_FILENAME);

            $result = static::client()->uploadApi()->upload(
                $tmpFile,
                [
                    'folder'          => $folder,
                    'public_id'       => $publicId,
                    'resource_type'   => 'image',
                    'overwrite'       => false,
                    'unique_filename' => true,
                ]
            );

            @unlink($tmpFile);

            return $result['secure_url'];
        } catch (\Throwable $e) {
            Log::error('Cloudinary uploadFromContent failed, falling back to local', [
                'error'  => $e->getMessage(),
                'folder' => $folder,
            ]);
            @unlink($tmpFile ?? '');
            $path = "{$folder}/{$filename}";
            Storage::disk('public')->put($path, $content);
            return $path;
        }
    }

    /**
     * Xóa ảnh/video trên Cloudinary (hoặc local).
     *
     * @param  string|null $url URL đầy đủ Cloudinary hoặc relative path local
     */
    public static function delete(?string $url): void
    {
        if (empty($url)) {
            return;
        }

        // Nếu là Cloudinary URL → extract public_id và gọi API destroy
        if (static::isCloudinaryUrl($url)) {
            if (!static::isConfigured()) {
                return;
            }
            try {
                $publicId = static::extractPublicId($url);
                if ($publicId) {
                    // Thử xóa cả image và video
                    try {
                        static::client()->uploadApi()->destroy($publicId, ['resource_type' => 'image']);
                    } catch (\Throwable $e) {
                        static::client()->uploadApi()->destroy($publicId, ['resource_type' => 'video']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Cloudinary delete failed', [
                    'url'   => $url,
                    'error' => $e->getMessage(),
                ]);
            }
            return;
        }

        // Nếu là URL external khác (Google/Bing...) → không xóa gì
        if (is_external_image_url($url)) {
            return;
        }

        // Đường dẫn tương đối local → xóa trên disk public
        Storage::disk('public')->delete($url);
    }

    /**
     * Kiểm tra URL có phải là Cloudinary URL không.
     */
    public static function isCloudinaryUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        return (bool) preg_match('#^https?://res\.cloudinary\.com/#i', $url);
    }

    /**
     * Extract public_id từ Cloudinary URL.
     * VD: https://res.cloudinary.com/abc/image/upload/v123/products/file.jpg
     *     → public_id = "products/file"
     */
    private static function extractPublicId(string $url): ?string
    {
        // Pattern: .../upload/v{numbers}/{public_id}.{ext}
        if (preg_match('#/upload/(?:v\d+/)?(.+)\.\w+$#', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
