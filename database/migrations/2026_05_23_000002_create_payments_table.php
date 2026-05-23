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
            $table->string('name');                        // VNPAY, Momo, COD...
            $table->string('code', 50)->unique();          // vnpay, momo, cod, bank
            $table->boolean('is_active')->default(true);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->json('config')->nullable();            // API key, secret...
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 0);
            $table->string('currency', 3)->default('VND');
            $table->enum('payment_method', ['COD', 'bank_transfer', 'momo', 'zalopay', 'vnpay'])->default('COD');
            $table->string('transaction_id', 255)->nullable()->comment('ID từ gateway');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
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
