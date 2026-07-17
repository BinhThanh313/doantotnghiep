<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CHẠY 1 LẦN DUY NHẤT — đổi các tài khoản demo cũ (email dạng
 * "...@electroshop.local", được tạo bởi bản ReviewSeeder / DemoInsightSeeder
 * trước) sang tên + email giống người dùng thật (theo DemoIdentityPool),
 * KHÔNG tạo user mới, KHÔNG đổi id — nên toàn bộ review/cart_items đã
 * gắn với các user này vẫn nguyên vẹn, không mất dữ liệu, không xung đột.
 *
 * An toàn chạy lại nhiều lần: nếu không còn user nào email cũ
 * (@electroshop.local) thì đơn giản là không có gì để đổi, không lỗi.
 *
 * Chạy: php artisan db:seed --class=HumanizeDemoUsersSeeder
 */
class HumanizeDemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $map = DemoIdentityPool::migrationMapFromOldEmails();
        $updated = 0;
        $skipped = 0;

        foreach ($map as $oldEmail => $identity) {
            // Nếu email mới đã bị 1 user KHÁC (không phải chính user đang
            // đổi) chiếm rồi thì bỏ qua để tuyệt đối không vi phạm unique
            // hay ghi đè nhầm 1 tài khoản thật trùng tên miền.
            $conflict = DB::table('users')
                ->where('email', $identity['email'])
                ->exists();

            if ($conflict) {
                $this->command?->warn("[Humanize] Bỏ qua {$oldEmail} vì email {$identity['email']} đã tồn tại.");
                $skipped++;
                continue;
            }

            $affected = DB::table('users')
                ->where('email', $oldEmail)
                ->update([
                    'name'       => $identity['name'],
                    'email'      => $identity['email'],
                    'updated_at' => now(),
                ]);

            $updated += $affected;
        }

        $this->command?->info("[Humanize] Đã đổi {$updated} tài khoản demo sang danh tính giống thật ({$skipped} bị bỏ qua do trùng email).");
    }
}