<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class FixCloudinary404 extends Command
{
    protected $signature = 'images:fix-404';
    protected $description = 'Fix broken Cloudinary images by re-uploading from local storage';

    public function handle()
    {
        $products = Product::whereNotNull('image')->get();
        foreach ($products as $product) {
            if (CloudinaryService::isCloudinaryUrl($product->image)) {
                $this->info("Đang xử lý lại ảnh: " . $product->image);
                
                // Trích xuất đường dẫn gốc. VD: https://.../v123456/products/product-4.png -> products/product-4.png
                if (preg_match('#/upload/(?:v\d+/)?(.+)$#', $product->image, $matches)) {
                    $localPath = $matches[1];
                    
                    if (Storage::disk('public')->exists($localPath)) {
                        $this->info("  -> Tìm thấy file gốc ở local: {$localPath}. Đang upload lại...");
                        
                        $content = Storage::disk('public')->get($localPath);
                        $filename = basename($localPath);
                        $folder = dirname($localPath);
                        if ($folder === '.') $folder = 'products';
                        
                        $newUrl = CloudinaryService::uploadFromContent($content, $filename, $folder);
                        
                        if (CloudinaryService::isCloudinaryUrl($newUrl)) {
                            $product->update(['image' => $newUrl]);
                            $this->info("  -> ✅ Đã upload thành công: " . $newUrl);
                        }
                    } else {
                        $this->warn("  -> ❌ Không tìm thấy file gốc ở local: {$localPath}");
                    }
                }
            }
        }

        $this->info("Đang kiểm tra thư viện ảnh (ProductImage)...");
        $images = \App\Models\ProductImage::whereNotNull('image_url')->get();
        foreach ($images as $img) {
            if (CloudinaryService::isCloudinaryUrl($img->image_url)) {
                $this->info("Đang xử lý lại ảnh thư viện: " . $img->image_url);
                if (preg_match('#/upload/(?:v\d+/)?(.+)$#', $img->image_url, $matches)) {
                    $localPath = $matches[1];
                    if (Storage::disk('public')->exists($localPath)) {
                        $this->info("  -> Tìm thấy file gốc ở local: {$localPath}. Đang upload lại...");
                        $content = Storage::disk('public')->get($localPath);
                        $filename = basename($localPath);
                        $folder = dirname($localPath);
                        if ($folder === '.') $folder = 'products';
                        
                        $newUrl = CloudinaryService::uploadFromContent($content, $filename, $folder);
                        if (CloudinaryService::isCloudinaryUrl($newUrl)) {
                            $img->update(['image_url' => $newUrl]);
                            $this->info("  -> ✅ Đã upload thành công: " . $newUrl);
                        }
                    }
                }
            }
        }
        
        $this->info("Hoàn tất!");
    }
}
