<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm index cho các cột hay dùng để lọc/sắp xếp danh sách sản phẩm
 * (trang chủ, shop, admin) — trước đây các query như
 * where('is_active', true)->orderByDesc('view_count')
 * phải quét toàn bộ bảng, gây chậm khi số lượng sản phẩm tăng lên.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('is_bestseller');
            $table->index('view_count');
            $table->index(['is_active', 'category_id']); // lọc theo danh mục + đang bán
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_bestseller']);
            $table->dropIndex(['view_count']);
            $table->dropIndex(['is_active', 'category_id']);
        });
    }
};