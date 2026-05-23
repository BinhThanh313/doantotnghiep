<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name')->nullable();
            $table->enum('discount_type', ['percent', 'fixed']);
            $table->decimal('discount_value', 15, 0);
            $table->decimal('min_amount', 15, 0)->default(0)->comment('Đơn tối thiểu');
            $table->decimal('max_discount', 15, 0)->nullable()->comment('Giảm tối đa (dùng cho percent)');
            $table->integer('max_uses')->nullable()->comment('Tổng số lần dùng tối đa');
            $table->integer('max_uses_per_user')->default(1)->comment('Mỗi user dùng tối đa');
            $table->integer('used_count')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->json('applicable_categories')->nullable()->comment('Áp dụng cho danh mục nào');
            $table->json('applicable_products')->nullable()->comment('Áp dụng cho sản phẩm nào');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Bảng lưu voucher đã dùng trong đơn hàng
        Schema::create('order_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_amount', 15, 0);
            $table->timestamps();
        });

        // Bảng lưu user đã dùng voucher (để giới hạn max_uses_per_user)
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('order_vouchers');
        Schema::dropIfExists('vouchers');
    }
};