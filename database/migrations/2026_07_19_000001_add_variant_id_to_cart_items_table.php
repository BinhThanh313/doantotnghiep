<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép giỏ hàng phân biệt được TỪNG BIẾN THỂ (màu/size...) của cùng
 * 1 sản phẩm, thay vì chỉ gộp chung theo product_id như trước.
 *
 * - variant_id NULLABLE: sản phẩm không có biến thể vẫn thêm vào giỏ
 *   bình thường (variant_id = null).
 * - nullOnDelete: nếu biến thể bị xoá cứng (hiếm khi xảy ra vì
 *   ProductVariant dùng SoftDeletes), dòng giỏ hàng không bị xoá theo mà
 *   chỉ mất liên kết variant, tránh mất dữ liệu giỏ hàng của khách.
 * - Bỏ unique(user_id, product_id) cũ: 1 sản phẩm giờ có thể xuất hiện
 *   nhiều dòng trong giỏ nếu khác biến thể. Việc chống trùng lặp
 *   (cùng product_id + cùng variant_id) được xử lý ở tầng ứng dụng
 *   (CartController) vì MySQL coi các giá trị NULL là khác nhau nên
 *   không thể enforce đúng ý nghĩa bằng unique index khi variant_id
 *   nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);

            $table->foreignId('variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->index(['user_id', 'product_id', 'variant_id'], 'cart_items_user_product_variant_index');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('cart_items_user_product_variant_index');
            $table->dropConstrainedForeignId('variant_id');
            $table->unique(['user_id', 'product_id']);
        });
    }
};