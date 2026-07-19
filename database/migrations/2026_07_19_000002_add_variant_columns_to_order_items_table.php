<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App\Models\OrderItem đã có sẵn $fillable + relation productVariant() +
 * helper deductStock()/restoreStock() cho product_variant_id, variant_name,
 * original_price, discount_percent — nhưng các cột này CHƯA từng được tạo
 * trong bảng order_items (chỉ có product_id, product_name, quantity, price
 * từ migration gốc). Vì vậy CheckoutController trước giờ không thể lưu biến
 * thể (màu) đã chọn vào đơn hàng. Migration này bổ sung đúng các cột model
 * đang mong đợi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->nullOnDelete();

            // Snapshot tên biến thể (VD: "Màu Đỏ") lúc đặt hàng — không phụ
            // thuộc vào việc sau này biến thể bị đổi tên/xoá.
            $table->string('variant_name')->nullable()->after('product_name');

            $table->decimal('original_price', 15, 0)->nullable()->after('price');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('original_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['variant_name', 'original_price', 'discount_percent']);
        });
    }
};