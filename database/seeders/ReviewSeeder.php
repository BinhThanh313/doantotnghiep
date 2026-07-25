<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seed đánh giá (review) demo cho toàn bộ sản phẩm để storefront trông
 * có dữ liệu thật khi demo bảo vệ.
 *
 * AN TOÀN CHẠY LẠI NHIỀU LẦN (idempotent) và KHÔNG BAO GIỜ đụng tới user
 * thật / review thật của khách. Toàn bộ review ở đây được gắn với các
 * tài khoản "seed.customerN@electroshop.local" do chính seeder này tạo
 * ra — không trùng với bất kỳ user đăng ký thật nào, nên updateOrCreate
 * theo (product_id, user_id) không có rủi ro ghi đè dữ liệu thật (bài
 * học từ lỗi cũ ở DemoInsightSeeder::seedNegativeReviews).
 *
 * Chạy riêng: php artisan db:seed --class=ReviewSeeder
 */
class ReviewSeeder extends Seeder
{
    private const POSITIVE_TITLES = [
        'Rất hài lòng', 'Đáng tiền', 'Sản phẩm tốt', 'Sẽ ủng hộ tiếp',
        'Giao hàng nhanh, đóng gói kỹ', 'Chất lượng như mô tả', null, null,
    ];

    private const POSITIVE_COMMENTS = [
        'Sản phẩm đúng như mô tả, đóng gói cẩn thận, giao hàng nhanh. Rất hài lòng.',
        'Dùng được vài ngày thấy ổn định, chất lượng tốt so với giá tiền.',
        'Shop tư vấn nhiệt tình, sản phẩm chính hãng, đầy đủ phụ kiện.',
        'Đóng gói kỹ, không móp méo, hoạt động tốt ngay khi mở hộp.',
        'Mua lần 2 rồi, vẫn tin tưởng chất lượng của shop.',
        'Thiết kế đẹp, dùng mượt, đáng đồng tiền bát gạo.',
        'Giao nhanh hơn dự kiến, sản phẩm y hình, rất ưng ý.',
    ];

    private const NEUTRAL_TITLES = ['Tạm ổn', 'Bình thường', null];

    private const NEUTRAL_COMMENTS = [
        'Sản phẩm dùng tạm ổn, không có gì nổi bật nhưng cũng không tệ.',
        'Đóng gói bình thường, giao hơi trễ 1 ngày so với dự kiến.',
        'Chất lượng ở mức chấp nhận được so với tầm giá.',
        'Ổn trong tầm giá, một vài chi tiết hoàn thiện chưa thật sự tốt.',
    ];

    private const NEGATIVE_TITLES = ['Chưa hài lòng lắm', 'Cần cải thiện'];

    private const NEGATIVE_COMMENTS = [
        'Sản phẩm nhận được có chút khác biệt so với hình, hơi thất vọng.',
        'Giao hàng chậm hơn dự kiến, đóng gói chưa thật sự chắc chắn.',
        'Chất lượng chưa như kỳ vọng ở mức giá này, cần cải thiện thêm.',
    ];

    public function run(): void
    {
        $customers = $this->getOrCreateCustomers();

        $products = Product::where('is_active', true)->orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->command?->warn('[ReviewSeeder] Không có sản phẩm active — bỏ qua.');
            return;
        }

        $totalReviews = 0;

        foreach ($products as $product) {
            // Mỗi sản phẩm nhận ngẫu nhiên 0-8 review, đa số có review để
            // trang chi tiết sản phẩm không bị trống, một số ít để trống
            // (0 review) cho tự nhiên.
            $reviewCount = (rand(1, 100) <= 10) ? 0 : rand(2, 8);
            if ($reviewCount === 0) {
                continue;
            }

            $chosenCustomers = $customers->shuffle()->take(min($reviewCount, $customers->count()));

            foreach ($chosenCustomers as $customer) {
                [$rating, $title, $comment] = $this->randomReviewContent();

                $review = Review::updateOrCreate(
                    ['product_id' => $product->id, 'user_id' => $customer->id],
                    [
                        'rating'            => $rating,
                        'title'             => $title,
                        'comment'           => $comment,
                        'is_visible'        => true,
                        'verified_purchase' => rand(1, 100) <= 65, // ~65% "đã mua hàng"
                        'helpful_count'     => rand(0, 100) <= 30 ? rand(1, 15) : 0,
                    ]
                );

                // Rải thời điểm tạo trong 90 ngày gần nhất cho tự nhiên
                DB::table('reviews')->where('id', $review->id)->update([
                    'created_at' => Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23)),
                    'updated_at' => Carbon::now(),
                ]);

                $totalReviews++;
            }
        }

        $this->command?->info("[ReviewSeeder] Đã seed {$totalReviews} review trên {$products->count()} sản phẩm.");
    }

    /**
     * Tạo (hoặc lấy lại nếu đã tồn tại) các tài khoản khách hàng demo
     * riêng cho việc seed review — firstOrCreate nên chạy lại nhiều lần
     * không tạo trùng, và không bao giờ đụng tới user thật.
     */
    private function getOrCreateCustomers()
    {
        $customers = collect();

        foreach (DemoIdentityPool::customers() as $identity) {
            $customers->push(User::firstOrCreate(
                ['email' => $identity['email']],
                [
                    'name'     => $identity['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('demo_seed_password'),
                    'role'     => 'user',
                ]
            ));
        }

        return $customers;
    }

    /**
     * Random rating + nội dung tương ứng, lệch về phía tích cực (giống
     * phân bố đánh giá thực tế của một shop bán hàng hoạt động tốt).
     * Tỉ lệ ~ 5★:35%, 4★:30%, 3★:18%, 2★:10%, 1★:7%.
     */
    private function randomReviewContent(): array
    {
        $roll = rand(1, 100);

        if ($roll <= 35) {
            $rating = 5;
        } elseif ($roll <= 65) {
            $rating = 4;
        } elseif ($roll <= 83) {
            $rating = 3;
        } elseif ($roll <= 93) {
            $rating = 2;
        } else {
            $rating = 1;
        }

        if ($rating >= 4) {
            $title   = self::POSITIVE_TITLES[array_rand(self::POSITIVE_TITLES)];
            $comment = self::POSITIVE_COMMENTS[array_rand(self::POSITIVE_COMMENTS)];
        } elseif ($rating === 3) {
            $title   = self::NEUTRAL_TITLES[array_rand(self::NEUTRAL_TITLES)];
            $comment = self::NEUTRAL_COMMENTS[array_rand(self::NEUTRAL_COMMENTS)];
        } else {
            $title   = self::NEGATIVE_TITLES[array_rand(self::NEGATIVE_TITLES)];
            $comment = self::NEGATIVE_COMMENTS[array_rand(self::NEGATIVE_COMMENTS)];
        }

        return [$rating, $title, $comment];
    }
}