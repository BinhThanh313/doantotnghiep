<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh sách yêu thích
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // Log trừ/cộng kho
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->integer('quantity_change');                 // Âm = xuất kho, Dương = nhập kho
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->enum('reason', ['purchase', 'return', 'adjustment', 'damage', 'import'])->default('adjustment');
            $table->unsignedBigInteger('reference_id')->nullable(); // order_id hoặc id khác
            $table->string('reference_type')->nullable();           // 'order', 'manual'...
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Thông báo trong app
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 50);                        // order_placed, order_shipped, ...
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('wishlists');
    }
};
