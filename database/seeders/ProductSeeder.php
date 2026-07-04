<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Tra theo tên danh mục thay vì random id, tránh sai lệch khi thứ tự
        // insert của CategorySeeder thay đổi.
        $categories = DB::table('categories')->pluck('id', 'name');

        // Mỗi sản phẩm được gán đúng danh mục thay vì random như trước đây
        // (nguyên nhân gây lỗi laptop bị xếp vào "Loa bluetooth").
        // "Camera" = máy quay hành trình / action cam, "Máy ảnh" = máy ảnh
        // chuyên nghiệp (mirrorless/DSLR) để 2 danh mục không bị trùng lặp
        // ý nghĩa và không danh mục nào bị bỏ trống.
        $products = [
            // Điện thoại
            ['iPhone 15 Pro Max', 'Điện thoại', 34990000, 36990000],
            ['Samsung Galaxy S24 Ultra', 'Điện thoại', 29990000, 32990000],
            ['Xiaomi 14 Pro', 'Điện thoại', 19990000, 22990000],
            ['OPPO Find X7', 'Điện thoại', 17990000, null],
            ['Vivo X100 Pro', 'Điện thoại', 18990000, 20990000],
            ['iPhone 14', 'Điện thoại', 22990000, 25990000],
            ['Samsung Galaxy A54', 'Điện thoại', 9990000, 11990000],
            ['Realme GT 5', 'Điện thoại', 13990000, null],
            ['iPhone 13', 'Điện thoại', 17990000, 19990000],
            ['Samsung Galaxy Z Fold5', 'Điện thoại', 45990000, 49990000],

            // Laptop
            ['MacBook Pro M3', 'Laptop', 54990000, 59990000],
            ['Dell XPS 15', 'Laptop', 42990000, 45990000],
            ['ASUS ROG Zephyrus', 'Laptop', 38990000, null],
            ['Lenovo ThinkPad X1', 'Laptop', 35990000, 38990000],
            ['HP Spectre x360', 'Laptop', 32990000, 34990000],
            ['MacBook Air M2', 'Laptop', 32990000, 35990000],
            ['Lenovo IdeaPad 5 Pro', 'Laptop', 21990000, 23990000],
            ['ASUS ZenBook 14', 'Laptop', 24990000, null],

            // Máy tính bảng
            ['iPad Pro M2', 'Máy tính bảng', 28990000, null],
            ['Samsung Galaxy Tab S9', 'Máy tính bảng', 22990000, 25990000],
            ['Xiaomi Pad 6 Pro', 'Máy tính bảng', 12990000, 14990000],
            ['iPad Air M1', 'Máy tính bảng', 18990000, 20990000],

            // Đồng hồ thông minh
            ['Apple Watch Ultra 2', 'Đồng hồ thông minh', 21990000, 23990000],
            ['Samsung Galaxy Watch 6', 'Đồng hồ thông minh', 8990000, 9990000],
            ['Garmin Fenix 7', 'Đồng hồ thông minh', 17990000, null],
            ['Apple Watch Series 9', 'Đồng hồ thông minh', 11990000, 13990000],
            ['Xiaomi Watch 2 Pro', 'Đồng hồ thông minh', 5990000, null],

            // Tai nghe
            ['Sony WH-1000XM5', 'Tai nghe', 8490000, 9490000],
            ['AirPods Pro 2', 'Tai nghe', 6990000, 7490000],
            ['Bose QuietComfort 45', 'Tai nghe', 7990000, 8990000],
            ['Samsung Galaxy Buds2 Pro', 'Tai nghe', 4990000, 5490000],
            ['Jabra Evolve2 75', 'Tai nghe', 9990000, null],

            // Camera (action cam)
            ['GoPro Hero 12', 'Camera', 11990000, 12990000],
            ['DJI Osmo Action 4', 'Camera', 8990000, null],
            ['Insta360 X3', 'Camera', 10990000, 11990000],

            // Máy ảnh (mirrorless / DSLR chuyên nghiệp)
            ['Sony Alpha A7 IV', 'Máy ảnh', 62990000, null],
            ['Canon EOS R6 Mark II', 'Máy ảnh', 58990000, 62990000],
            ['Fujifilm X-T5', 'Máy ảnh', 42990000, 45990000],
            ['Nikon Z6 III', 'Máy ảnh', 55990000, 59990000],
            ['Canon EOS 90D', 'Máy ảnh', 38990000, null],

            // Tivi
            ['Samsung QLED 4K 55"', 'Tivi', 22990000, 25990000],
            ['Sony Bravia XR 65"', 'Tivi', 35990000, null],
            ['LG OLED C3 55"', 'Tivi', 32990000, 35990000],
            ['TCL QLED 50"', 'Tivi', 12990000, 14990000],
            ['Panasonic OLED 55"', 'Tivi', 28990000, null],

            // Loa bluetooth
            ['JBL Charge 5', 'Loa bluetooth', 3990000, 4490000],
            ['Sony SRS-XB43', 'Loa bluetooth', 4490000, null],
            ['Marshall Emberton II', 'Loa bluetooth', 3290000, 3690000],
            ['JBL PartyBox 310', 'Loa bluetooth', 8990000, 9990000],
            ['Bose SoundLink Flex', 'Loa bluetooth', 3690000, 3990000],

            // Phụ kiện (trước đây danh mục này chưa có sản phẩm nào)
            ['Chuột Logitech MX Master 3S', 'Phụ kiện', 2190000, 2490000],
            ['Bàn phím cơ Keychron K8', 'Phụ kiện', 1690000, 1890000],
            ['Sạc dự phòng Anker 20000mAh', 'Phụ kiện', 990000, 1190000],
            ['Cáp sạc nhanh USB-C to USB-C 100W', 'Phụ kiện', 290000, 350000],
            ['Ốp lưng chống sốc iPhone 15 Pro Max', 'Phụ kiện', 250000, 350000],
            ['Giá đỡ laptop nhôm đa năng', 'Phụ kiện', 450000, null],
            ['Túi chống sốc laptop 15.6 inch', 'Phụ kiện', 350000, 420000],
            ['Hub chuyển đổi USB-C 7 in 1', 'Phụ kiện', 690000, 790000],
        ];

        // Đánh dấu bestseller cho 1-2 sản phẩm tiêu biểu mỗi danh mục, để
        // nhánh fallback cold-start (RecommendationService::forUser() /
        // ItemBasedRecommendationService::topUp()) luôn có dữ liệu trả về
        // thay vì rỗng khi user chưa có lịch sử mua/xem (VD: admin).
        $bestsellerNames = [
            'iPhone 15 Pro Max', 'Samsung Galaxy S24 Ultra',
            'MacBook Pro M3', 'Dell XPS 15',
            'iPad Pro M2',
            'Apple Watch Ultra 2',
            'Sony WH-1000XM5', 'AirPods Pro 2',
            'Sony Alpha A7 IV',
            'Samsung QLED 4K 55"',
            'JBL Charge 5',
            'Sạc dự phòng Anker 20000mAh',
        ];

        foreach ($products as $index => [$name, $categoryName, $price, $originalPrice]) {
            if (!isset($categories[$categoryName])) {
                // Tránh insert sai âm thầm nếu tên danh mục gõ lệch với CategorySeeder
                throw new \RuntimeException("Không tìm thấy danh mục '{$categoryName}' — kiểm tra lại CategorySeeder.");
            }

            DB::table('products')->insert([
                'category_id'    => $categories[$categoryName],
                'name'           => $name,
                'slug'           => Str::slug($name) . '-' . ($index + 1),
                'description'    => 'Sản phẩm chất lượng cao, bảo hành chính hãng 12 tháng. ' . $name . ' mang lại trải nghiệm tuyệt vời cho người dùng.',
                'price'          => $price,
                'original_price' => $originalPrice,
                'stock'          => rand(5, 100),
                'is_new'         => $index < 15,
                'is_bestseller'  => in_array($name, $bestsellerNames, true),
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}