<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test đăng nhập/phân quyền admin — bao gồm các fix bảo mật đã áp dụng:
 * - throttle:login chống brute-force (RateLimiter 'login' trong AppServiceProvider)
 * - middleware 'admin' chặn user thường truy cập route /api/admin/*
 * - guard chặn tự xóa chính mình / xóa admin cuối cùng (UserController::destroy)
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'     => 'admin',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    private function makeCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'     => 'user',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    public function test_admin_can_login_with_correct_credentials()
    {
        $admin = $this->makeAdmin(['email' => 'admin@shop.test']);

        $response = $this->postJson('/api/admin/login', [
            'email'    => 'admin@shop.test',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_fails_with_wrong_password()
    {
        $this->makeAdmin(['email' => 'admin@shop.test']);

        $response = $this->postJson('/api/admin/login', [
            'email'    => 'admin@shop.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_admin_user_cannot_login_to_admin_panel()
    {
        $this->makeCustomer(['email' => 'customer@shop.test']);

        $response = $this->postJson('/api/admin/login', [
            'email'    => 'customer@shop.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_repeated_failed_logins_get_throttled()
    {
        $this->makeAdmin(['email' => 'admin@shop.test']);

        // RateLimiter 'login' giới hạn 5 lần/phút theo cặp (email + IP)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/admin/login', [
                'email'    => 'admin@shop.test',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        // Lần thứ 6 phải bị chặn bởi throttle, dù mật khẩu đúng hay sai
        $this->postJson('/api/admin/login', [
            'email'    => 'admin@shop.test',
            'password' => 'password123',
        ])->assertStatus(429);
    }

    public function test_unauthenticated_request_cannot_access_admin_routes()
    {
        $this->getJson('/api/admin/dashboard')->assertStatus(401);
    }

    public function test_regular_customer_token_cannot_access_admin_routes()
    {
        $customer = $this->makeCustomer();

        Sanctum::actingAs($customer);

        $this->getJson('/api/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_admin_cannot_delete_their_own_account()
    {
        $admin = $this->makeAdmin();
        $this->makeAdmin(); // đảm bảo còn admin khác, không rơi vào case "admin cuối cùng"

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Không thể tự xóa tài khoản của chính mình']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_delete_the_last_remaining_admin()
    {
        // Xóa hết user seed mặc định (nếu có) để kiểm soát chính xác số lượng admin
        User::where('role', 'admin')->delete();

        $admin1 = $this->makeAdmin(); // actor
        $admin2 = $this->makeAdmin(); // target — sẽ bị xóa ở bước 1

        // Còn 2 admin -> xóa admin2 vẫn hợp lệ, admin1 vẫn còn lại
        Sanctum::actingAs($admin1);

        $this->deleteJson("/api/admin/users/{$admin2->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
        $this->assertSame(1, User::where('role', 'admin')->count());

        // Giờ chỉ còn đúng 1 admin — không ai được xóa nốt admin cuối cùng này,
        // kể cả chính họ (guard tự-xóa cũng chặn, nhưng thông điệp khác nhau)
        Sanctum::actingAs($admin1);

        $this->deleteJson("/api/admin/users/{$admin1->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin1->id]);
    }

    public function test_admin_token_carries_configured_expiration()
    {
        // Xác nhận config đã được set (không còn null = vĩnh viễn)
        $this->assertNotNull(config('sanctum.expiration'));
        $this->assertGreaterThan(0, config('sanctum.expiration'));
    }
}