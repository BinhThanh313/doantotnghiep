<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Sinh ảnh placeholder dạng SVG cho sản phẩm chưa có ảnh riêng: nền màu
 * theo danh mục + tên sản phẩm. Dùng cho mục đích demo/đồ án, tránh vấn
 * đề bản quyền khi dùng ảnh thương hiệu thật, và không cần kết nối mạng.
 *
 * Chạy: php artisan products:generate-placeholder-images
 * Thêm --force để ghi đè cả những sản phẩm đã có ảnh.
 */
class GenerateProductPlaceholderImages extends Command
{
    protected $signature = 'products:generate-placeholder-images {--force : Ghi đè cả sản phẩm đã có ảnh}';
    protected $description = 'Sinh ảnh placeholder SVG theo danh mục cho các sản phẩm chưa có ảnh riêng';

    private array $categoryColors = [
        'Điện thoại'          => ['#2563eb', '#1e40af'],
        'Laptop'              => ['#7c3aed', '#5b21b6'],
        'Máy tính bảng'       => ['#0891b2', '#0e7490'],
        'Đồng hồ thông minh'  => ['#059669', '#047857'],
        'Tai nghe'            => ['#dc2626', '#991b1b'],
        'Camera'              => ['#ea580c', '#c2410c'],
        'Máy ảnh'             => ['#d97706', '#b45309'],
        'Tivi'                => ['#4f46e5', '#3730a3'],
        'Loa bluetooth'       => ['#db2777', '#9d174d'],
        'Phụ kiện'            => ['#64748b', '#475569'],
    ];

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $dir = 'products';
        Storage::disk('public')->makeDirectory($dir);

        $products = Product::with('category')
            ->when(!$force, fn ($q) => $q->whereNull('image'))
            ->get();

        if ($products->isEmpty()) {
            $this->info('Không có sản phẩm nào cần tạo ảnh (dùng --force để ghi đè ảnh đã có).');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($products->count());

        foreach ($products as $product) {
            $categoryName = $product->category->name ?? 'Phụ kiện';
            [$colorStart, $colorEnd] = $this->categoryColors[$categoryName] ?? ['#64748b', '#475569'];

            $svg = $this->buildSvg($product->name, $categoryName, $colorStart, $colorEnd);
            $path = "{$dir}/{$product->slug}.svg";
            Storage::disk('public')->put($path, $svg);

            $product->update(['image' => $path]);

            // Đồng bộ vào gallery ảnh (ProductImage) làm ảnh chính, tránh
            // ảnh đại diện (products.image) và gallery bị lệch nhau.
            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'is_primary' => true],
                ['image_url' => $path, 'alt_text' => $product->name, 'sort_order' => 0]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Đã tạo ảnh placeholder cho {$products->count()} sản phẩm.");
        $this->comment('Nhắc: nếu chưa chạy "php artisan storage:link" thì hãy chạy để ảnh hiển thị được ra ngoài public/.');

        return self::SUCCESS;
    }

    private function buildSvg(string $name, string $categoryName, string $colorStart, string $colorEnd): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES);
        $safeCategory = htmlspecialchars($categoryName, ENT_QUOTES);

        // Tự xuống dòng tên sản phẩm nếu quá dài để không bị tràn khung ảnh
        $words = explode(' ', $safeName);
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $test = trim($current . ' ' . $word);
            if (mb_strlen($test) > 18 && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        $lineHeight = 42;
        $startY = 300 - (count($lines) - 1) * $lineHeight / 2;
        $textLines = '';
        foreach ($lines as $i => $line) {
            $y = $startY + $i * $lineHeight;
            $textLines .= "<text x=\"300\" y=\"{$y}\" font-family=\"Arial, sans-serif\" font-size=\"32\" font-weight=\"700\" fill=\"#ffffff\" text-anchor=\"middle\">{$line}</text>\n";
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$colorStart}" />
      <stop offset="100%" stop-color="{$colorEnd}" />
    </linearGradient>
  </defs>
  <rect width="600" height="600" fill="url(#bg)" />
  <circle cx="300" cy="180" r="80" fill="#ffffff" fill-opacity="0.12" />
  {$textLines}
  <text x="300" y="380" font-family="Arial, sans-serif" font-size="20" fill="#ffffff" fill-opacity="0.8" text-anchor="middle">{$safeCategory}</text>
  <text x="300" y="560" font-family="Arial, sans-serif" font-size="14" fill="#ffffff" fill-opacity="0.6" text-anchor="middle">Ảnh minh họa demo — Electro Shop</text>
</svg>
SVG;
    }
}