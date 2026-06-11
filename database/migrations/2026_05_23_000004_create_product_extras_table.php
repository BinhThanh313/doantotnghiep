<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gallery ảnh sản phẩm (nhiều ảnh)
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_url');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes(); // add to product_variants table
        });

        // Biến thể sản phẩm (Size, Color, ...)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku', 100)->unique()->nullable();
            $table->string('name');                          // "Size M - Đỏ"
            $table->string('attributes')->nullable();        // JSON: {"size":"M","color":"red"}
            $table->decimal('price', 15, 0)->nullable();     // null = dùng giá gốc
            $table->decimal('original_price', 15, 0)->nullable();
            $table->integer('stock')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // add to product_variants table
        });

        // Thêm cột SEO vào bảng products
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_description', 255)->nullable()->after('description');
            $table->string('meta_keywords', 255)->nullable()->after('meta_description');
            $table->string('og_image')->nullable()->after('meta_keywords');
            $table->string('tags')->nullable()->after('og_image');
            $table->integer('view_count')->default(0)->after('stock');
            $table->softDeletes(); // add to products table
        });

        // Thêm cột vào orders để lưu shipping_fee + voucher
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_fee', 15, 0)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 15, 0)->default(0)->after('shipping_fee');
            $table->string('tracking_number')->nullable()->after('notes');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid')->after('status');
            $table->string('province')->nullable()->after('address');
            $table->softDeletes(); // add to orders table
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_fee', 'discount_amount', 'tracking_number', 'payment_status', 'province']);
            $table->dropSoftDeletes();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['meta_description', 'meta_keywords', 'og_image', 'tags', 'view_count']);
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
    }
};
