<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Thông tin khách hàng
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('address');
            
            // Thông tin đơn hàng
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->decimal('total_amount', 15, 0);
            
            // Trạng thái đơn hàng (Đã gộp mở rộng)
            $table->enum('status', [
                'pending', 'processing', 'ready_to_ship', 'shipped', 'delivered', 'completed', 'cancelled'
            ])->default('pending');
            
            // Phương thức thanh toán (Đã gộp mở rộng)
            $table->enum('payment_method', ['cod', 'bank'])->default('cod');
            
            $table->text('notes')->nullable();

            // Xử lý Trả hàng / Hoàn tiền
            $table->text('return_reason')->nullable();
            $table->decimal('refund_amount', 15, 0)->nullable();
            $table->enum('return_status', ['none', 'requested', 'refunded', 'rejected'])->default('none');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};