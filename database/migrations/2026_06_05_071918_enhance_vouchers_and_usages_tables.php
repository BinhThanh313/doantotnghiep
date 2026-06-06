<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce max_uses_per_user ở cấp database:
 * Thêm unique index (voucher_id, user_id) + check constraint trên voucher_usages.
 * Đồng thời thêm index performance cho vouchers.used_count.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Đảm bảo unique (voucher_id, user_id) — ngăn race condition duplicate insert
        //    Nếu index đã tồn tại từ migration gốc thì bỏ qua
        if (!$this->indexExists('voucher_usages', 'voucher_usages_voucher_id_user_id_unique')) {
            Schema::table('voucher_usages', function (Blueprint $table) {
                $table->unique(['voucher_id', 'user_id'], 'voucher_usages_voucher_id_user_id_unique');
            });
        }

        // 2. Thêm index trên used_count để WHERE used_count < max_uses nhanh hơn
        Schema::table('vouchers', function (Blueprint $table) {
            if (!$this->indexExists('vouchers', 'vouchers_used_count_index')) {
                $table->index('used_count', 'vouchers_used_count_index');
            }
            if (!$this->indexExists('vouchers', 'vouchers_is_active_end_date_index')) {
                $table->index(['is_active', 'end_date'], 'vouchers_is_active_end_date_index');
            }
        });

        // 3. Thêm cột attempts vào voucher_usages để track số lần dùng (nếu > 1/user)
        Schema::table('voucher_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_usages', 'used_count')) {
                $table->unsignedTinyInteger('used_count')->default(1)->after('order_id')
                    ->comment('Số lần user này đã dùng voucher này');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->dropIndex('voucher_usages_voucher_id_user_id_unique');
            if (Schema::hasColumn('voucher_usages', 'used_count')) {
                $table->dropColumn('used_count');
            }
        });
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex('vouchers_used_count_index');
            $table->dropIndex('vouchers_is_active_end_date_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $sm      = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes($table);
        return isset($indexes[$index]) || isset($indexes[strtolower($index)]);
    }
};