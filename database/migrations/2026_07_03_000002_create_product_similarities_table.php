<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng lưu kết quả tính sẵn (cache) độ tương đồng cosine giữa các cặp
 * sản phẩm, dùng cho Item-based Collaborative Filtering. Được tính lại
 * định kỳ bởi ItemSimilarityService::build() (qua Artisan command
 * `recommendation:build-similarity`), không tính real-time vì tốn kém.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('similar_product_id')->constrained('products')->onDelete('cascade');
            $table->float('score'); // cosine similarity, 0.0 - 1.0
            $table->timestamps();

            $table->unique(['product_id', 'similar_product_id']);
            $table->index(['product_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_similarities');
    }
};