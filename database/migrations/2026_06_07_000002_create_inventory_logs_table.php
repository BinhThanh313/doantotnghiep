<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->onDelete('set null');

            $table->integer('quantity_change')->comment('Âm = trừ kho, Dương = nhập kho');
            $table->integer('stock_before');
            $table->integer('stock_after');

            $table->enum('reason', [
                'purchase',     // Khách đặt hàng
                'restock',      // Nhập kho
                'adjustment',   // Điều chỉnh thủ công
                'return',       // Khách trả hàng
                'cancel',       // Hủy đơn → hoàn lại
                'damage',       // Hàng hỏng
            ])->default('purchase');

            $table->unsignedBigInteger('reference_id')->nullable()->index()
                  ->comment('order_id hoặc ID của tài liệu liên quan');
            $table->string('reference_type', 50)->nullable()
                  ->comment('order, manual, return, ...');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamps();

            // Index thường dùng nhất
            $table->index(['product_id', 'created_at']);
            $table->index(['reference_id', 'reference_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};