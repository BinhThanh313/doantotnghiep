<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 0);
            $table->string('currency', 3)->default('VND');
            $table->enum('payment_method', ['COD', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'paypal', 'stripe'])->default('COD');
            $table->string('transaction_id', 255)->nullable()->comment('ID từ gateway');
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'refunding', 'refunded'])->default('pending');
            $table->json('gateway_response')->nullable()->comment('Response raw từ gateway');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};