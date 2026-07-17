<?php

namespace Database\Seeders;

/**
 * Danh sách CỐ ĐỊNH (deterministic) 25 danh tính khách hàng demo, dùng
 * chung cho ReviewSeeder + DemoInsightSeeder.
 *
 * Lý do cố định thay vì random mỗi lần chạy: đảm bảo idempotent — chạy
 * seeder lại nhiều lần luôn khớp đúng user cũ qua email (firstOrCreate),
 * không tạo trùng, không phải lo va chạm với email khách hàng thật vì
 * đây là danh sách do chính project kiểm soát.
 *
 * Có thể tự thêm/sửa tên miễn giữ email không trùng nhau và không trùng
 * với bất kỳ tài khoản thật nào trong hệ thống.
 */
class DemoIdentityPool
{
    private const POOL = [
        ['name' => 'Nguyễn Văn An',    'email' => 'nguyenvanan88@gmail.com'],
        ['name' => 'Trần Thị Bích',    'email' => 'tranthib1994@gmail.com'],
        ['name' => 'Lê Hoàng Nam',     'email' => 'lehoangnam90@gmail.com'],
        ['name' => 'Phạm Thị Hoa',     'email' => 'phamthihoa211@gmail.com'],
        ['name' => 'Hoàng Văn Đức',    'email' => 'hoangvanduc99@gmail.com'],
        ['name' => 'Vũ Thị Lan',       'email' => 'vuthilan85@gmail.com'],
        ['name' => 'Đặng Minh Quân',   'email' => 'dangminhquan97@gmail.com'],
        ['name' => 'Bùi Thị Thu',      'email' => 'buithithu2000@gmail.com'],
        ['name' => 'Đỗ Văn Hùng',      'email' => 'dovanhung1993@gmail.com'],
        ['name' => 'Ngô Thị Mai',      'email' => 'ngothimai86@gmail.com'],
        ['name' => 'Dương Văn Long',   'email' => 'duongvanlong22@gmail.com'],
        ['name' => 'Lý Thị Ngọc',      'email' => 'lythingoc95@gmail.com'],
        ['name' => 'Phan Văn Kiên',    'email' => 'phanvankien91@gmail.com'],
        ['name' => 'Đinh Thị Yến',     'email' => 'dinhthiyen98@gmail.com'],
        ['name' => 'Trịnh Văn Tùng',   'email' => 'trinhvantung87@gmail.com'],
        ['name' => 'Mai Thị Hằng',     'email' => 'maithihang03@gmail.com'],
        ['name' => 'Tô Văn Phúc',      'email' => 'tovanphuc19@gmail.com'],
        ['name' => 'Đoàn Thị Thảo',    'email' => 'doanthithao96@gmail.com'],
        ['name' => 'Lương Văn Sơn',    'email' => 'luongvanson84@gmail.com'],
        ['name' => 'Chu Thị Vân',      'email' => 'chuthivan2001@gmail.com'],
        // 5 danh tính riêng cho DemoInsightSeeder (giỏ hàng bỏ quên + review xấu)
        ['name' => 'Vương Văn Khoa',   'email' => 'vuongvankhoa93@gmail.com'],
        ['name' => 'Huỳnh Thị Diệu',   'email' => 'huynhthidieu89@gmail.com'],
        ['name' => 'Cao Văn Bảo',      'email' => 'caovanbao17@gmail.com'],
        ['name' => 'Tạ Thị Quỳnh',     'email' => 'tathiquynh92@gmail.com'],
        ['name' => 'Lâm Văn Đạt',      'email' => 'lamvandat05@gmail.com'],
    ];

    /** 20 danh tính dùng cho ReviewSeeder (khách để lại review rải trên các sản phẩm) */
    public static function customers(): array
    {
        return array_slice(self::POOL, 0, 20);
    }

    /** 5 danh tính dùng cho DemoInsightSeeder (review xấu + giỏ hàng bỏ quên) */
    public static function insightDemoUsers(): array
    {
        return array_slice(self::POOL, 20, 5);
    }

    /**
     * Map "email cũ" (đã dùng ở bản seeder trước, dạng @electroshop.local)
     * -> danh tính mới, dùng để đổi tên user đã tạo trước đó (giữ nguyên
     * id, không tạo user mới, không đụng review/cart đã gắn vào id đó).
     */
    public static function migrationMapFromOldEmails(): array
    {
        $map = [];

        foreach (self::customers() as $i => $identity) {
            $map["seed.customer" . ($i + 1) . "@electroshop.local"] = $identity;
        }

        foreach (self::insightDemoUsers() as $i => $identity) {
            $map["demo.reviewer" . ($i + 1) . "@electroshop.local"] = $identity;
        }

        return $map;
    }
}