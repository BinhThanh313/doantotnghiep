<?php

namespace App\Services;

/**
 * Sinh thông số kỹ thuật DEMO cho sản phẩm, dựa theo danh mục + mức giá
 * (price tier). Đây là dữ liệu mô phỏng phục vụ minh họa hệ thống catalog
 * và chatbot — KHÔNG phải thông số chính xác 100% của sản phẩm thật, thiết
 * kế để nhất quán theo phân khúc giá (giá càng cao, cấu hình càng tốt)
 * thay vì random hoàn toàn, giúp demo hợp lý và dễ giải thích.
 *
 * Mỗi phương thức category trả về mảng các dòng dạng:
 *   ['group' => 'Tên nhóm', 'label' => 'Tên thông số', 'value' => '...', 'unit' => null]
 */
class ProductSpecificationGenerator
{
    public function generate(string $categoryName, string $productName, float $price): array
    {
        return match ($categoryName) {
            'Điện thoại'          => $this->phone($price),
            'Laptop'              => $this->laptop($price),
            'Máy tính bảng'       => $this->tablet($price),
            'Đồng hồ thông minh'  => $this->watch($price),
            'Tai nghe'            => $this->headphone($price),
            'Camera'              => $this->actionCam($price),
            'Máy ảnh'             => $this->camera($price),
            'Tivi'                => $this->tv($price),
            'Loa bluetooth'       => $this->speaker($price),
            'Phụ kiện'            => $this->accessory($productName, $price),
            default               => [],
        };
    }

    /** Xác định phân khúc theo giá (đơn vị: VNĐ) */
    private function tier(float $price, array $thresholds): string
    {
        // $thresholds dạng ['entry' => 0, 'mid' => x, 'high' => y, 'flagship' => z]
        $tier = 'entry';
        foreach ($thresholds as $name => $min) {
            if ($price >= $min) {
                $tier = $name;
            }
        }
        return $tier;
    }

    private function pick(string $tier, array $map)
    {
        return $map[$tier] ?? end($map);
    }

    // ==================== ĐIỆN THOẠI ====================
    private function phone(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 10_000_000, 'high' => 18_000_000, 'flagship' => 28_000_000]);

        $screenSize = $this->pick($tier, ['entry' => '6.1', 'mid' => '6.4', 'high' => '6.6', 'flagship' => '6.7']);
        $resolution = $this->pick($tier, ['entry' => '2340 x 1080', 'mid' => '2400 x 1080', 'high' => '2556 x 1179', 'flagship' => '3120 x 1440']);
        $refresh    = $this->pick($tier, ['entry' => '60', 'mid' => '90', 'high' => '120', 'flagship' => '120']);
        $mainCam    = $this->pick($tier, ['entry' => '50 MP', 'mid' => '50 MP & 8 MP', 'high' => '48 MP & 12 MP', 'flagship' => '200 MP & 12 MP & 10 MP']);
        $chipset    = $this->pick($tier, ['entry' => 'Snapdragon 6 Gen 1', 'mid' => 'Snapdragon 7 Gen 3', 'high' => 'Apple A16 Bionic / Snapdragon 8 Gen 2', 'flagship' => 'Apple A17 Pro / Snapdragon 8 Gen 3']);
        $ram        = $this->pick($tier, ['entry' => 6, 'mid' => 8, 'high' => 8, 'flagship' => 12]);
        $storage    = $this->pick($tier, ['entry' => 128, 'mid' => 128, 'high' => 256, 'flagship' => 512]);
        $battery    = $this->pick($tier, ['entry' => '5000', 'mid' => '5000', 'high' => '4422', 'flagship' => '5000']);
        $charge     = $this->pick($tier, ['entry' => '18', 'mid' => '33', 'high' => '27', 'flagship' => '45']);

        return [
            ['group' => 'Màn hình', 'label' => 'Kích thước màn hình', 'value' => $screenSize, 'unit' => 'inches'],
            ['group' => 'Màn hình', 'label' => 'Độ phân giải màn hình', 'value' => $resolution, 'unit' => 'pixels'],
            ['group' => 'Màn hình', 'label' => 'Tần số quét', 'value' => $refresh, 'unit' => 'Hz'],
            ['group' => 'Màn hình', 'label' => 'Công nghệ màn hình', 'value' => $tier === 'entry' ? 'IPS LCD' : 'Super AMOLED / OLED', 'unit' => null],
            ['group' => 'Camera sau', 'label' => 'Camera sau', 'value' => $mainCam, 'unit' => null],
            ['group' => 'Camera sau', 'label' => 'Tính năng camera', 'value' => "HDR\nChống rung quang học (OIS)\nQuay chậm\nChế độ ban đêm", 'unit' => null],
            ['group' => 'Camera trước', 'label' => 'Camera trước', 'value' => $tier === 'flagship' ? '12 MP' : '8 MP', 'unit' => null],
            ['group' => 'Vi xử lý & đồ họa', 'label' => 'Chipset', 'value' => $chipset, 'unit' => null],
            ['group' => 'RAM & lưu trữ', 'label' => 'Dung lượng RAM', 'value' => (string) $ram, 'unit' => 'GB'],
            ['group' => 'RAM & lưu trữ', 'label' => 'Bộ nhớ trong', 'value' => (string) $storage, 'unit' => 'GB'],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Pin', 'value' => $battery, 'unit' => 'mAh'],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Hỗ trợ sạc tối đa', 'value' => $charge, 'unit' => 'W'],
            ['group' => 'Giao tiếp & kết nối', 'label' => 'Hỗ trợ mạng', 'value' => $tier === 'entry' ? '4G' : '5G', 'unit' => null],
            ['group' => 'Giao tiếp & kết nối', 'label' => 'Thẻ SIM', 'value' => '2 SIM (nano-SIM và eSIM)', 'unit' => null],
            ['group' => 'Thông số khác', 'label' => 'Chỉ số kháng nước, bụi', 'value' => $tier === 'entry' ? 'IP54' : 'IP68', 'unit' => null],
        ];
    }

    // ==================== LAPTOP ====================
    private function laptop(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 25_000_000, 'high' => 38_000_000, 'flagship' => 50_000_000]);

        $cpu     = $this->pick($tier, ['entry' => 'Intel Core i5 thế hệ 12', 'mid' => 'Intel Core i7 thế hệ 13 / Apple M2', 'high' => 'Intel Core i7 thế hệ 13 / Apple M3', 'flagship' => 'Intel Core i9 thế hệ 13 / Apple M3 Pro']);
        $ram     = $this->pick($tier, ['entry' => 8, 'mid' => 16, 'high' => 16, 'flagship' => 32]);
        $storage = $this->pick($tier, ['entry' => 256, 'mid' => 512, 'high' => 512, 'flagship' => 1024]);
        $screen  = $this->pick($tier, ['entry' => '14', 'mid' => '14', 'high' => '15.6', 'flagship' => '16']);
        $gpu     = $this->pick($tier, ['entry' => 'Intel Iris Xe (onboard)', 'mid' => 'NVIDIA RTX 3050', 'high' => 'NVIDIA RTX 4050 / Apple GPU 10-core', 'flagship' => 'NVIDIA RTX 4070 / Apple GPU 18-core']);

        return [
            ['group' => 'Màn hình', 'label' => 'Kích thước màn hình', 'value' => $screen, 'unit' => 'inches'],
            ['group' => 'Màn hình', 'label' => 'Độ phân giải', 'value' => $tier === 'entry' ? 'Full HD (1920x1080)' : '2.5K/2.8K trở lên', 'unit' => null],
            ['group' => 'Màn hình', 'label' => 'Tần số quét', 'value' => $tier === 'entry' ? '60' : '90-120', 'unit' => 'Hz'],
            ['group' => 'Vi xử lý & đồ họa', 'label' => 'CPU', 'value' => $cpu, 'unit' => null],
            ['group' => 'Vi xử lý & đồ họa', 'label' => 'Card đồ họa', 'value' => $gpu, 'unit' => null],
            ['group' => 'RAM & lưu trữ', 'label' => 'Dung lượng RAM', 'value' => (string) $ram, 'unit' => 'GB'],
            ['group' => 'RAM & lưu trữ', 'label' => 'Ổ cứng', 'value' => "SSD NVMe {$storage}GB", 'unit' => null],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Dung lượng pin', 'value' => $tier === 'flagship' ? '90-99' : '50-70', 'unit' => 'Wh'],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Cổng sạc', 'value' => 'USB Type-C (Power Delivery)', 'unit' => null],
            ['group' => 'Thiết kế & Trọng lượng', 'label' => 'Trọng lượng', 'value' => $tier === 'flagship' ? '2.1-2.5' : '1.3-1.8', 'unit' => 'kg'],
            ['group' => 'Cổng kết nối', 'label' => 'Cổng kết nối', 'value' => "USB-C x2\nUSB-A x1\nHDMI\nJack tai nghe 3.5mm", 'unit' => null],
            ['group' => 'Cổng kết nối', 'label' => 'Wi-Fi', 'value' => 'Wi-Fi 6', 'unit' => null],
            ['group' => 'Tính năng khác', 'label' => 'Hệ điều hành', 'value' => str_contains(strtolower($cpu), 'apple') ? 'macOS' : 'Windows 11', 'unit' => null],
        ];
    }

    // ==================== MÁY TÍNH BẢNG ====================
    private function tablet(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 15_000_000, 'high' => 22_000_000]);

        return [
            ['group' => 'Màn hình', 'label' => 'Kích thước màn hình', 'value' => $tier === 'entry' ? '10.9' : '11-12.9', 'unit' => 'inches'],
            ['group' => 'Màn hình', 'label' => 'Độ phân giải', 'value' => $tier === 'entry' ? '2360 x 1640' : '2732 x 2048', 'unit' => 'pixels'],
            ['group' => 'Vi xử lý & đồ họa', 'label' => 'Chipset', 'value' => $this->pick($tier, ['entry' => 'A14 Bionic / Snapdragon 7 Gen 1', 'mid' => 'M1', 'high' => 'M2']), 'unit' => null],
            ['group' => 'RAM & lưu trữ', 'label' => 'Dung lượng RAM', 'value' => $this->pick($tier, ['entry' => '6', 'mid' => '8', 'high' => '8']), 'unit' => 'GB'],
            ['group' => 'RAM & lưu trữ', 'label' => 'Bộ nhớ trong', 'value' => $this->pick($tier, ['entry' => '128', 'mid' => '256', 'high' => '256']), 'unit' => 'GB'],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Pin', 'value' => '8-10 giờ sử dụng liên tục', 'unit' => null],
            ['group' => 'Tính năng khác', 'label' => 'Hỗ trợ bút cảm ứng', 'value' => 'Có', 'unit' => null],
            ['group' => 'Tính năng khác', 'label' => 'Hệ điều hành', 'value' => 'iPadOS / Android', 'unit' => null],
        ];
    }

    // ==================== ĐỒNG HỒ THÔNG MINH ====================
    private function watch(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 10_000_000, 'high' => 18_000_000]);

        return [
            ['group' => 'Màn hình', 'label' => 'Kích thước mặt', 'value' => $this->pick($tier, ['entry' => '1.4', 'mid' => '1.5', 'high' => '1.9']), 'unit' => 'inches'],
            ['group' => 'Màn hình', 'label' => 'Công nghệ màn hình', 'value' => $tier === 'entry' ? 'AMOLED' : 'LTPO OLED (luôn hiển thị)', 'unit' => null],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Thời lượng pin', 'value' => $this->pick($tier, ['entry' => '2-3', 'mid' => '1-2', 'high' => '10-36']), 'unit' => 'ngày'],
            ['group' => 'Cảm biến & sức khỏe', 'label' => 'Cảm biến', 'value' => "Nhịp tim\nSpO2\nGia tốc kế\nLa bàn số\nGPS", 'unit' => null],
            ['group' => 'Thông số khác', 'label' => 'Kháng nước', 'value' => $tier === 'high' ? '10 ATM' : '5 ATM', 'unit' => null],
            ['group' => 'Tính năng khác', 'label' => 'Hệ điều hành', 'value' => 'watchOS / Wear OS', 'unit' => null],
        ];
    }

    // ==================== TAI NGHE ====================
    private function headphone(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 5_000_000, 'high' => 8_000_000]);

        return [
            ['group' => 'Âm thanh', 'label' => 'Kích thước driver', 'value' => $this->pick($tier, ['entry' => '10', 'mid' => '10', 'high' => '30x40 (over-ear)']), 'unit' => 'mm'],
            ['group' => 'Âm thanh', 'label' => 'Chống ồn chủ động (ANC)', 'value' => $tier === 'entry' ? 'Không' : 'Có', 'unit' => null],
            ['group' => 'Kết nối', 'label' => 'Chuẩn Bluetooth', 'value' => $tier === 'high' ? '5.3' : '5.0-5.2', 'unit' => null],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Thời lượng pin', 'value' => $this->pick($tier, ['entry' => '24 (kèm hộp sạc)', 'mid' => '30 (kèm hộp sạc)', 'high' => '30 (over-ear, bật ANC)']), 'unit' => 'giờ'],
            ['group' => 'Thông số khác', 'label' => 'Kháng nước', 'value' => $tier === 'entry' ? 'IPX4' : 'IPX4 (tuỳ dòng)', 'unit' => null],
        ];
    }

    // ==================== CAMERA HÀNH TRÌNH (ACTION CAM) ====================
    private function actionCam(float $price): array
    {
        return [
            ['group' => 'Camera', 'label' => 'Độ phân giải video tối đa', 'value' => '5.3K@60fps / 4K@120fps', 'unit' => null],
            ['group' => 'Camera', 'label' => 'Chống rung', 'value' => 'HyperSmooth / RockSteady (chống rung điện tử)', 'unit' => null],
            ['group' => 'Thông số khác', 'label' => 'Kháng nước', 'value' => 'Chống nước tới 10m không cần vỏ bọc', 'unit' => null],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Thời lượng pin quay', 'value' => '60-90', 'unit' => 'phút quay 4K'],
            ['group' => 'Lưu trữ', 'label' => 'Loại thẻ nhớ', 'value' => 'microSD (tối đa 512GB)', 'unit' => null],
        ];
    }

    // ==================== MÁY ẢNH (MIRRORLESS/DSLR) ====================
    private function camera(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 45_000_000, 'high' => 55_000_000]);

        return [
            ['group' => 'Cảm biến', 'label' => 'Loại cảm biến', 'value' => $tier === 'entry' ? 'APS-C CMOS' : 'Full-frame CMOS', 'unit' => null],
            ['group' => 'Cảm biến', 'label' => 'Độ phân giải', 'value' => $this->pick($tier, ['entry' => '24.2', 'mid' => '33', 'high' => '61']), 'unit' => 'MP'],
            ['group' => 'Camera', 'label' => 'Dải ISO', 'value' => $tier === 'entry' ? '100-25600' : '100-51200 (mở rộng 204800)', 'unit' => null],
            ['group' => 'Camera', 'label' => 'Quay video tối đa', 'value' => $tier === 'entry' ? '4K@30fps' : '4K@60fps / 8K@30fps', 'unit' => null],
            ['group' => 'Ống kính & lấy nét', 'label' => 'Ngàm ống kính', 'value' => 'Ngàm E / RF / Z (tùy hãng)', 'unit' => null],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Số ảnh chụp mỗi lần sạc', 'value' => '500-700', 'unit' => 'ảnh'],
        ];
    }

    // ==================== TIVI ====================
    private function tv(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 15_000_000, 'high' => 30_000_000]);

        return [
            ['group' => 'Màn hình', 'label' => 'Công nghệ màn hình', 'value' => $this->pick($tier, ['entry' => 'LED', 'mid' => 'QLED', 'high' => 'OLED']), 'unit' => null],
            ['group' => 'Màn hình', 'label' => 'Độ phân giải', 'value' => '4K UHD (3840 x 2160)', 'unit' => 'pixels'],
            ['group' => 'Màn hình', 'label' => 'Tần số quét', 'value' => $tier === 'high' ? '120' : '60', 'unit' => 'Hz'],
            ['group' => 'Màn hình', 'label' => 'Hỗ trợ HDR', 'value' => 'HDR10+, Dolby Vision', 'unit' => null],
            ['group' => 'Âm thanh', 'label' => 'Công suất loa', 'value' => $tier === 'high' ? '40-60' : '20', 'unit' => 'W'],
            ['group' => 'Tính năng khác', 'label' => 'Hệ điều hành', 'value' => 'Google TV / Tizen / webOS', 'unit' => null],
            ['group' => 'Cổng kết nối', 'label' => 'Cổng kết nối', 'value' => "HDMI 2.1 x3\nUSB x2\nWi-Fi\nBluetooth", 'unit' => null],
        ];
    }

    // ==================== LOA BLUETOOTH ====================
    private function speaker(float $price): array
    {
        $tier = $this->tier($price, ['entry' => 0, 'mid' => 4_000_000, 'high' => 8_000_000]);

        return [
            ['group' => 'Âm thanh', 'label' => 'Công suất', 'value' => $this->pick($tier, ['entry' => '10-20', 'mid' => '20-30', 'high' => '100-160 (loa kéo)']), 'unit' => 'W'],
            ['group' => 'Kết nối', 'label' => 'Chuẩn Bluetooth', 'value' => '5.0-5.3', 'unit' => null],
            ['group' => 'Pin & công nghệ sạc', 'label' => 'Thời lượng pin', 'value' => $tier === 'high' ? '8-12 (loa kéo có dây nguồn phụ)' : '15-20', 'unit' => 'giờ'],
            ['group' => 'Thông số khác', 'label' => 'Kháng nước', 'value' => $tier === 'entry' ? 'IP67' : 'IPX4 (tuỳ dòng)', 'unit' => null],
        ];
    }

    // ==================== PHỤ KIỆN (theo loại sản phẩm) ====================
    private function accessory(string $name, float $price): array
    {
        $n = mb_strtolower($name);

        if (str_contains($n, 'chuột')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Độ nhạy (DPI)', 'value' => '200 - 8000', 'unit' => 'DPI'],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Kết nối', 'value' => 'Bluetooth / USB Receiver 2.4GHz', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Số nút bấm', 'value' => '6-7 nút có thể tùy chỉnh', 'unit' => null],
                ['group' => 'Pin', 'label' => 'Thời lượng pin', 'value' => 'Lên đến 70 ngày', 'unit' => null],
            ];
        }

        if (str_contains($n, 'bàn phím')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Loại switch', 'value' => 'Cơ (Blue/Brown/Red switch tùy chọn)', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Kết nối', 'value' => 'Bluetooth / USB-C có dây', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Layout', 'value' => 'TKL (87 phím) / Full-size (104 phím)', 'unit' => null],
                ['group' => 'Pin', 'label' => 'Thời lượng pin', 'value' => 'Lên đến 240 giờ (đèn nền tắt)', 'unit' => null],
            ];
        }

        if (str_contains($n, 'sạc dự phòng')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Dung lượng', 'value' => '20000', 'unit' => 'mAh'],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Công suất sạc ra tối đa', 'value' => '20-22.5', 'unit' => 'W'],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Cổng kết nối', 'value' => 'USB-C x1, USB-A x1', 'unit' => null],
            ];
        }

        if (str_contains($n, 'cáp')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Chuẩn cổng', 'value' => 'USB-C to USB-C', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Công suất truyền tải tối đa', 'value' => '100', 'unit' => 'W'],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Chiều dài', 'value' => '1', 'unit' => 'm'],
            ];
        }

        if (str_contains($n, 'ốp lưng')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Chất liệu', 'value' => 'TPU chống sốc + viền PC cứng', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Khả năng chống sốc', 'value' => 'Chuẩn quân đội, chịu va đập từ độ cao 1.5m', 'unit' => null],
            ];
        }

        if (str_contains($n, 'giá đỡ')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Chất liệu', 'value' => 'Hợp kim nhôm', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Tải trọng tối đa', 'value' => '5', 'unit' => 'kg'],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Góc điều chỉnh', 'value' => 'Có thể gập, điều chỉnh độ cao/góc nghiêng', 'unit' => null],
            ];
        }

        if (str_contains($n, 'túi chống sốc')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Chất liệu', 'value' => 'Vải chống nước + lớp lót chống sốc', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Kích thước phù hợp', 'value' => 'Laptop 15.6 inch', 'unit' => null],
            ];
        }

        if (str_contains($n, 'hub')) {
            return [
                ['group' => 'Thông số kỹ thuật', 'label' => 'Số cổng', 'value' => 'USB-C x1, USB-A x3, HDMI x1, SD/TF x2', 'unit' => null],
                ['group' => 'Thông số kỹ thuật', 'label' => 'Hỗ trợ xuất hình', 'value' => '4K@30fps qua cổng HDMI', 'unit' => null],
            ];
        }

        return [
            ['group' => 'Thông số kỹ thuật', 'label' => 'Chất liệu', 'value' => 'Cao cấp, bền bỉ', 'unit' => null],
        ];
    }
}