<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // GHN, ViettelPost, Ahamove
            $table->string('code', 50)->unique();
            $table->string('api_key')->nullable();
            $table->string('api_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('base_fee', 15, 0)->default(0);
            $table->decimal('per_km_fee', 15, 0)->default(0);
            $table->timestamps();
        });

        // Phí ship theo tỉnh thành (flat fee)
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained('shipping_carriers')->onDelete('cascade');
            $table->string('province');          // Tên tỉnh/thành phố
            $table->string('region')->nullable(); // Miền: north, central, south
            $table->decimal('fee', 15, 0);
            $table->integer('estimated_days')->default(3);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('carrier_id')->nullable()->constrained('shipping_carriers')->onDelete('set null');
            $table->string('tracking_number', 100)->nullable();
            $table->decimal('shipping_fee', 15, 0)->default(0);
            $table->dateTime('estimated_delivery')->nullable();
            $table->dateTime('actual_delivery')->nullable();
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'failed', 'returned'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_carriers');
    }
};
