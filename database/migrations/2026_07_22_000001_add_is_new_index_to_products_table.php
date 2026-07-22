<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung index còn thiếu cho cột is_new — dùng ở tab "Hàng mới về" trên
 * trang chủ (HomeController) và trang bestsellers (ShopController), luôn
 * đi kèm điều kiện is_active nhưng trước đó chưa có index nào bao phủ
 * cặp cột này (migration add_performance_indexes_to_products_table chỉ
 * thêm is_active, is_bestseller, view_count).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'is_new']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_new']);
        });
    }
};