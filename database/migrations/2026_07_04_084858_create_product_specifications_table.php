<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng thông số kỹ thuật sản phẩm, thiết kế theo mô hình EAV (Entity-
 * Attribute-Value) có nhóm (group), phù hợp cho catalog nhiều ngành hàng
 * khác nhau (điện thoại, laptop, tivi, loa...) với bộ thông số hoàn toàn
 * khác nhau mà KHÔNG cần thêm bảng/cột mới cho từng ngành hàng.
 *
 * group_name: tên nhóm hiển thị (VD: "Màn hình", "Camera sau", "Pin & sạc")
 * label:      tên thông số (VD: "Kích thước màn hình")
 * value:      giá trị (lưu dạng text để chứa được cả text nhiều dòng,
 *             VD: liệt kê nhiều tính năng camera)
 * unit:       đơn vị hiển thị riêng nếu cần (VD: "GB", "mAh"), có thể để
 *             trống nếu đơn vị đã nằm sẵn trong value
 * sort_order: thứ tự hiển thị trong nhóm, đảm bảo hiển thị đúng thứ tự
 *             như bảng thông số gốc thay vì random theo id
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('group_name');
            $table->string('label');
            $table->text('value');
            $table->string('unit')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};