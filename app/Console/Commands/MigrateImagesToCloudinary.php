<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Artisan command để migrate toàn bộ ảnh đang lưu local lên Cloudinary
 * và cập nhật URL trong database.
 *
 * Usage: php artisan images:migrate-cloudinary
 */
class MigrateImagesToCloudinary extends Command
{
    protected $signature = 'images:migrate-cloudinary
                            {--dry-run : Chỉ liệt kê, không upload/cập nhật gì}';

    protected $description = 'Migrate tất cả ảnh từ local storage lên Cloudinary và cập nhật URL trong DB';

    private int $uploaded = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle(): int
    {
        if (!CloudinaryService::isConfigured()) {
            $this->error('❌ Cloudinary chưa được cấu hình! Hãy set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET trong .env');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Chế độ DRY-RUN — chỉ liệt kê, không upload/cập nhật gì.');
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('  📦 Migrate ảnh sản phẩm (products.image)');
        $this->info('═══════════════════════════════════════════════');
        $this->migrateProducts($dryRun);

        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('  🖼️  Migrate gallery (product_images.image_url)');
        $this->info('═══════════════════════════════════════════════');
        $this->migrateProductImages($dryRun);

        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('  🔄 Migrate biến thể (product_variants.image)');
        $this->info('═══════════════════════════════════════════════');
        $this->migrateProductVariants($dryRun);

        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('  ⭐ Migrate ảnh review (review_images.image_url)');
        $this->info('═══════════════════════════════════════════════');
        $this->migrateReviewImages($dryRun);

        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('  🎬 Migrate video review (reviews.video_url)');
        $this->info('═══════════════════════════════════════════════');
        $this->migrateReviewVideos($dryRun);

        // Summary
        $this->info('');
        $this->info('═══════════════════════════════════════════════');
        $this->info('  📊 KẾT QUẢ');
        $this->info('═══════════════════════════════════════════════');
        $this->table(
            ['Metric', 'Count'],
            [
                ['✅ Uploaded thành công', $this->uploaded],
                ['⏭️  Bỏ qua (đã là URL ngoài/Cloudinary)', $this->skipped],
                ['❌ Lỗi', $this->failed],
            ]
        );

        return self::SUCCESS;
    }

    private function migrateProducts(bool $dryRun): void
    {
        $products = Product::whereNotNull('image')->where('image', '!=', '')->get();
        $bar = $this->output->createProgressBar($products->count());

        foreach ($products as $product) {
            $this->processPath(
                $product->image,
                'products',
                $dryRun,
                function (string $newUrl) use ($product) {
                    $product->update(['image' => $newUrl]);
                },
                "Product #{$product->id}"
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateProductImages(bool $dryRun): void
    {
        $images = ProductImage::whereNotNull('image_url')->where('image_url', '!=', '')->get();
        $bar = $this->output->createProgressBar($images->count());

        foreach ($images as $image) {
            $this->processPath(
                $image->image_url,
                'products',
                $dryRun,
                function (string $newUrl) use ($image) {
                    $image->update(['image_url' => $newUrl]);

                    // Nếu ảnh này là primary → đồng bộ vào products.image
                    if ($image->is_primary) {
                        $image->product?->update(['image' => $newUrl]);
                    }
                },
                "ProductImage #{$image->id}"
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateProductVariants(bool $dryRun): void
    {
        $variants = ProductVariant::whereNotNull('image')->where('image', '!=', '')->get();
        $bar = $this->output->createProgressBar($variants->count());

        foreach ($variants as $variant) {
            $this->processPath(
                $variant->image,
                'products/variants',
                $dryRun,
                function (string $newUrl) use ($variant) {
                    $variant->update(['image' => $newUrl]);
                },
                "ProductVariant #{$variant->id}"
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateReviewImages(bool $dryRun): void
    {
        $images = ReviewImage::whereNotNull('image_url')->where('image_url', '!=', '')->get();
        $bar = $this->output->createProgressBar($images->count());

        foreach ($images as $image) {
            $this->processPath(
                $image->image_url,
                'reviews',
                $dryRun,
                function (string $newUrl) use ($image) {
                    $image->update(['image_url' => $newUrl]);
                },
                "ReviewImage #{$image->id}"
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateReviewVideos(bool $dryRun): void
    {
        $reviews = Review::whereNotNull('video_url')->where('video_url', '!=', '')->get();
        $bar = $this->output->createProgressBar($reviews->count());

        foreach ($reviews as $review) {
            $this->processPath(
                $review->video_url,
                'reviews/videos',
                $dryRun,
                function (string $newUrl) use ($review) {
                    $review->update(['video_url' => $newUrl]);
                },
                "Review #{$review->id} video"
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Xử lý 1 path: nếu là relative path (local) → upload lên Cloudinary,
     * nếu đã là URL ngoài/Cloudinary → bỏ qua.
     */
    private function processPath(
        string $path,
        string $folder,
        bool $dryRun,
        callable $updateCallback,
        string $label
    ): void {
        // Đã là URL ngoài hoặc Cloudinary → bỏ qua
        if (is_external_image_url($path) || CloudinaryService::isCloudinaryUrl($path)) {
            $this->skipped++;
            return;
        }

        // Kiểm tra file tồn tại trên local storage
        if (!Storage::disk('public')->exists($path)) {
            $this->line("  ⚠️  {$label}: File không tồn tại trên local: {$path}");
            $this->skipped++;
            return;
        }

        if ($dryRun) {
            $this->line("  📄 {$label}: {$path} → sẽ upload lên Cloudinary/{$folder}");
            $this->uploaded++;
            return;
        }

        try {
            $content = Storage::disk('public')->get($path);
            $filename = basename($path);

            $newUrl = CloudinaryService::uploadFromContent($content, $filename, $folder);

            // Kiểm tra upload thành công (trả về Cloudinary URL, không phải local path)
            if (CloudinaryService::isCloudinaryUrl($newUrl)) {
                $updateCallback($newUrl);
                $this->uploaded++;
            } else {
                $this->line("  ⚠️  {$label}: Upload trả về local path thay vì Cloudinary URL");
                $this->failed++;
            }
        } catch (\Throwable $e) {
            $this->error("  ❌ {$label}: {$e->getMessage()}");
            $this->failed++;
        }

        // Delay 1s để tránh lỗi "Slow Down, Out of Processing Capacity" của Cloudinary Free tier
        sleep(1);
    }
}
