<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test đồng bộ orders.payment_status <-> payments.status khi admin đổi
 * trạng thái đơn hàng thủ công. Đây là bug đã sửa trước đó: đơn COD được
 * đánh dấu "delivered" nhưng bản ghi Payment vẫn kẹt ở "pending".
 */
class OrderPaymentSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCodOrder(): Order
    {
        $category = Category::create(['name' => 'Phụ kiện', 'slug' => 'phu-kien-' . uniqid()]);
        $product  = Product::create([
            'category_id' => $category->id,
            'name'        => 'Chuột không dây',
            'slug'        => 'chuot-khong-day-' . uniqid(),
            'price'       => 300000,
            'stock'       => 20,
        ]);

        $order = Order::create([
            'customer_name'  => 'Nguyễn Văn A',
            'customer_email' => 'a@example.com',
            'customer_phone' => '0900000000',
            'address'        => '123 Test Street',
            'total_amount'   => $product->price,
            'payment_method' => 'cod',
            'status'         => 'pending',
            'payment_status' => 'unpaid',
        ]);

        Payment::create([
            'order_id'       => $order->id,
            'amount'         => $product->price,
            'payment_method' => 'cod',
            'status'         => 'pending',
        ]);

        return $order;
    }

    public function test_marking_cod_order_payment_as_paid_syncs_the_payment_record()
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder();

        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/orders/{$order->id}", [
                'status'         => 'delivered',
                'payment_status' => 'paid',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);

        // Điểm mấu chốt của bug đã fix: bản ghi payments phải được đồng
        // bộ theo, không được kẹt lại ở 'pending'
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status'   => 'success',
        ]);
    }

    public function test_bulk_update_payment_status_also_syncs_payment_records()
    {
        Mail::fake();

        $admin  = $this->makeAdmin();
        $order1 = $this->makeCodOrder();
        $order2 = $this->makeCodOrder();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/orders/bulk', [
                'ids'            => [$order1->id, $order2->id],
                'action'         => 'update_payment_status',
                'payment_status' => 'paid',
            ])
            ->assertOk();

        foreach ([$order1, $order2] as $order) {
            $this->assertDatabaseHas('orders', [
                'id'             => $order->id,
                'payment_status' => 'paid',
            ]);
            $this->assertDatabaseHas('payments', [
                'order_id' => $order->id,
                'status'   => 'success',
            ]);
        }
    }

    public function test_marking_order_as_refunded_syncs_payment_status_to_refunded()
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder();
        $order->update(['payment_status' => 'paid']);
        $order->payment->update(['status' => 'success']);

        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/orders/{$order->id}", [
                'payment_status' => 'refunded',
            ])
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status'   => 'refunded',
        ]);
    }
}