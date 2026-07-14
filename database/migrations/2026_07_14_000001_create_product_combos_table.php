<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('combo_product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedTinyInteger('discount_percent')->default(5);
            $table->boolean('is_active')->default(true);
            $table->float('similarity_score')->nullable()
                  ->comment('Điểm tương đồng tại thời điểm tạo combo (từ product_similarities), chỉ để tham khảo');
            $table->timestamps();

            $table->unique(['product_id', 'combo_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combos');
    }
};
