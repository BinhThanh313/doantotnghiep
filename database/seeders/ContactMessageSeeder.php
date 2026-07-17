<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seed tin nhắn liên hệ demo, dùng chung 20 tài khoản khách hàng trong
 * DemoIdentityPool (đã dùng cho ReviewSeeder) — không tạo user mới,
 * không đụng dữ liệu thật.
 *
 * AN TOÀN CHẠY LẠI NHIỀU LẦN (idempotent): match theo cặp (user_id,
 * subject) — mỗi khách demo chỉ gửi đúng 1 chủ đề cố định nên chạy lại
 * không tạo trùng, không đụng tin nhắn thật của khách (vì user thật
 * không bao giờ trùng id với 20 tài khoản demo này).
 *
 * Chạy: php artisan db:seed --class=ContactMessageSeeder
 */
class ContactMessageSeeder extends Seeder
{
    private const TOPICS = [
        [
            'subject' => 'Hỏi về tình trạng đơn hàng',
            'message' => 'Chào shop, mình đặt hàng mấy hôm rồi mà chưa thấy cập nhật trạng thái vận chuyển. Shop kiểm tra giúp mình với ạ, cảm ơn.',
        ],
        [
            'subject' => 'Hỏi về chính sách bảo hành',
            'message' => 'Cho mình hỏi sản phẩm bên shop bảo hành bao lâu và bảo hành ở đâu nếu lỗi phát sinh sau khi mua ạ?',
        ],
        [
            'subject' => 'Phản hồi về thời gian giao hàng',
            'message' => 'Đơn hàng của mình giao chậm hơn dự kiến 2 ngày, mong shop lưu ý cải thiện thêm ở khâu vận chuyển.',
        ],
        [
            'subject' => 'Tư vấn trước khi mua',
            'message' => 'Mình đang phân vân giữa 2 mẫu sản phẩm bên shop, có bạn tư vấn nào hỗ trợ mình so sánh giúp không ạ?',
        ],
        [
            'subject' => 'Cảm ơn shop',
            'message' => 'Mình mới nhận hàng, đóng gói kỹ và đúng như mô tả. Cảm ơn shop, chắc chắn sẽ ủng hộ tiếp!',
        ],
        [
            'subject' => 'Yêu cầu đổi/trả sản phẩm',
            'message' => 'Sản phẩm mình nhận bị lỗi nhỏ ở phần vỏ ngoài, mình muốn đổi sản phẩm khác, shop hướng dẫn giúp mình quy trình với ạ.',
        ],
        [
            'subject' => 'Hỏi về xuất hoá đơn',
            'message' => 'Bên mình mua để dùng cho công ty, cho mình hỏi shop có xuất hoá đơn VAT không và cần cung cấp thông tin gì ạ?',
        ],
        [
            'subject' => 'Hỗ trợ kỹ thuật sản phẩm',
            'message' => 'Sản phẩm mình mua không lên nguồn sau khi sạc, mình đã thử vài cách nhưng chưa được, mong shop hỗ trợ hướng dẫn ạ.',
        ],
        [
            'subject' => 'Hỏi về chương trình khuyến mãi',
            'message' => 'Shop cho mình hỏi voucher giảm giá đang áp dụng có dùng chung với chương trình flash sale được không ạ?',
        ],
        [
            'subject' => 'Góp ý về website',
            'message' => 'Mình thấy trang web của shop khá dễ dùng, chỉ góp ý phần tìm kiếm sản phẩm nên có thêm bộ lọc theo giá tiền.',
        ],
        [
            'subject' => 'Hỏi về địa chỉ cửa hàng',
            'message' => 'Shop có cửa hàng offline để mình xem trực tiếp sản phẩm trước khi mua không ạ? Nếu có cho mình xin địa chỉ.',
        ],
        [
            'subject' => 'Khiếu nại về đơn hàng bị huỷ',
            'message' => 'Đơn hàng của mình tự nhiên bị chuyển sang trạng thái huỷ mà mình không yêu cầu huỷ, shop kiểm tra giúp mình nguyên nhân với.',
        ],
    ];

    public function run(): void
    {
        $customers = $this->getCustomers();
        if ($customers->isEmpty()) {
            $this->command?->warn('[ContactMessageSeeder] Không có tài khoản demo nào — chạy ReviewSeeder trước để tạo.');
            return;
        }

        $total = 0;

        foreach (self::TOPICS as $i => $topic) {
            $customer = $customers[$i % $customers->count()];

            $isRead = rand(1, 100) <= 50;

            $contact = ContactMessage::updateOrCreate(
                ['user_id' => $customer->id, 'subject' => $topic['subject']],
                [
                    'name'    => $customer->name,
                    'email'   => $customer->email,
                    'phone'   => rand(1, 100) <= 60 ? $this->fakeVietnamesePhone() : null,
                    'message' => $topic['message'],
                    'is_read' => $isRead,
                ]
            );

            DB::table('contact_messages')->where('id', $contact->id)->update([
                'created_at' => Carbon::now()->subDays(rand(0, 45))->subHours(rand(0, 23)),
                'updated_at' => Carbon::now(),
            ]);

            $total++;
        }

        $this->command?->info("[ContactMessageSeeder] Đã seed {$total} tin nhắn liên hệ.");
    }

    /**
     * Lấy lại các tài khoản demo đã có sẵn (KHÔNG tự tạo mới ở đây) —
     * đảm bảo chạy sau ReviewSeeder / HumanizeDemoUsersSeeder. Nếu vì lý
     * do gì đó user demo chưa tồn tại, tự tạo bổ sung theo đúng danh
     * tính trong DemoIdentityPool để không phụ thuộc cứng thứ tự chạy.
     */
    private function getCustomers()
    {
        $customers = collect();

        foreach (DemoIdentityPool::customers() as $identity) {
            $customers->push(User::firstOrCreate(
                ['email' => $identity['email']],
                [
                    'name'     => $identity['name'],
                    'password' => Hash::make('demo_seed_password'),
                    'role'     => 'user',
                ]
            ));
        }

        return $customers;
    }

    private function fakeVietnamesePhone(): string
    {
        $prefixes = ['090', '091', '093', '094', '096', '097', '098', '032', '033', '070'];
        $prefix = $prefixes[array_rand($prefixes)];
        return $prefix . rand(1000000, 9999999);
    }
}