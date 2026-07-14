<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            // Mỗi user chỉ có 1 dòng cho mỗi sản phẩm trong giỏ
            $table->unique(['user_id', 'product_id']);

            // Dùng cho query "giỏ hàng bị bỏ quên" (admin insight #7):
            // created_at cũ hơn N giờ mà không phát sinh order tương ứng
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
