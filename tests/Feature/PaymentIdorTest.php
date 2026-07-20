<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test chống IDOR (Insecure Direct Object Reference) trên các endpoint
 * thanh toán — khách hàng A không được xem/thao tác lên đơn hàng của
 * khách hàng B chỉ bằng cách đổi ID trên URL.
 */
class PaymentIdorTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderWithPayment(User $owner, string $paymentStatus = 'success'): Order
    {
        $category = Category::create(['name' => 'Laptop', 'slug' => 'laptop-' . uniqid()]);

        $product = Product::create([
            'category_id' => $category->id,
            'name'        => 'Laptop test',
            'slug'        => 'laptop-test-' . uniqid(),
            'price'       => 15000000,
            'stock'       => 10,
        ]);

        $order = Order::create([
            'user_id'         => $owner->id,
            'customer_name'   => $owner->name,
            'customer_email'  => $owner->email,
            'customer_phone'  => '0900000000',
            'address'         => '123 Test Street',
            'total_amount'    => $product->price,
            'payment_method'  => 'bank',
            'payment_status'  => 'paid',
        ]);

        Payment::create([
            'order_id'       => $order->id,
            'amount'         => $product->price,
            'payment_method' => 'bank',
            'status'         => $paymentStatus,
        ]);

        return $order;
    }

    public function test_owner_can_view_their_own_payment_status()
    {
        $owner = User::factory()->create();
        $order = $this->makeOrderWithPayment($owner);

        Sanctum::actingAs($owner);

        $this->getJson("/api/payment/{$order->payment->id}/status")
            ->assertOk()
            ->assertJsonFragment(['order_id' => $order->id]);
    }

    public function test_other_customer_cannot_view_someone_elses_payment_status()
    {
        $owner    = User::factory()->create();
        $attacker = User::factory()->create();
        $order    = $this->makeOrderWithPayment($owner);

        Sanctum::actingAs($attacker);

        $this->getJson("/api/payment/{$order->payment->id}/status")
            ->assertStatus(403);
    }

    public function test_other_customer_cannot_request_refund_on_someone_elses_payment()
    {
        $owner    = User::factory()->create();
        $attacker = User::factory()->create();
        $order    = $this->makeOrderWithPayment($owner, paymentStatus: 'success');

        Sanctum::actingAs($attacker);

        $this->postJson("/api/payment/{$order->payment->id}/refund", [
            'reason' => 'Tôi muốn tiền của người khác',
        ])->assertStatus(403);

        $this->assertDatabaseHas('payments', [
            'id'     => $order->payment->id,
            'status' => 'success', // vẫn nguyên trạng thái cũ, không bị đổi
        ]);
    }

    public function test_other_customer_cannot_create_payment_session_for_someone_elses_order()
    {
        $owner    = User::factory()->create();
        $attacker = User::factory()->create();

        $category = Category::create(['name' => 'Điện thoại', 'slug' => 'dien-thoai-' . uniqid()]);
        $product  = Product::create([
            'category_id' => $category->id,
            'name'        => 'Điện thoại test',
            'slug'        => 'dien-thoai-test-' . uniqid(),
            'price'       => 5000000,
            'stock'       => 10,
        ]);

        $order = Order::create([
            'user_id'        => $owner->id,
            'customer_name'  => $owner->name,
            'customer_email' => $owner->email,
            'customer_phone' => '0900000000',
            'address'        => '123 Test Street',
            'total_amount'   => $product->price,
            'payment_method' => 'bank',
            'payment_status' => 'unpaid',
        ]);

        Sanctum::actingAs($attacker);

        $this->postJson('/api/payment/create', [
            'order_id' => $order->id,
            'method'   => 'bank',
        ])->assertStatus(403);
    }
}