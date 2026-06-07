<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 100)->index()->comment('order_placed, order_status_changed, order_refunded, ...');
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('related_id')->nullable()->index()->comment('order_id, product_id, ...');
            $table->string('related_type', 50)->nullable()->comment('order, product, ...');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();

            // Index tổng hợp: lấy thông báo chưa đọc của user
            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};