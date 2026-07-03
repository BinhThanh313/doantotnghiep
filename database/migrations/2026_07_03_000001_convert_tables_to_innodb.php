<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chuyển toàn bộ bảng trong database từ MyISAM sang InnoDB.
 *
 * Lý do: MyISAM không hỗ trợ transaction (DB::beginTransaction/rollBack
 * vô tác dụng) và KHÔNG enforce foreign key constraint (mọi khai báo
 * ->constrained()->onDelete('cascade') trong các migration trước đó bị
 * bỏ qua âm thầm). Đây là nguyên nhân khiến lệnh `recommendation:evaluate`
 * làm mất dữ liệu benchmark dù có rollback.
 *
 * ALTER TABLE ... ENGINE=InnoDB KHÔNG làm mất dữ liệu hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        $database = DB::getDatabaseName();

        $tables = DB::select(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND ENGINE != 'InnoDB' AND TABLE_TYPE = 'BASE TABLE'",
            [$database]
        );

        foreach ($tables as $table) {
            $name = $table->TABLE_NAME;
            // Bỏ qua bảng migrations của Laravel để tránh rủi ro không cần thiết
            if ($name === 'migrations') {
                continue;
            }

            DB::statement("ALTER TABLE `{$name}` ENGINE=InnoDB");
        }
    }

    public function down(): void
    {
        // Không hỗ trợ rollback về MyISAM vì sẽ làm mất khả năng
        // transaction/foreign key vốn là mục đích của migration này.
    }
};