<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
        $this->user = User::factory()->create();
        Redis::del("cart:{$this->user->id}");

        $category = Category::create(['name' => 'X', 'slug' => 'x']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Item', 'slug' => 'item', 'sku' => 'IT-001',
            'price' => 100, 'stock_quantity' => 10,
        ]);
    }

    public function test_checkout_throws_on_empty_cart(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cart is empty.');
        $this->service->checkoutFromCart($this->user);
    }

    public function test_checkout_creates_order_and_items(): void
    {
        Redis::setex("cart:{$this->user->id}", 3600, json_encode([
            $this->product->id => 3,
        ]));

        $order = $this->service->checkoutFromCart($this->user);

        $this->assertEquals($this->user->id, $order->user_id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(300, $order->subtotal);
        $this->assertCount(1, $order->items);
        $this->assertEquals(3, $order->items->first()->quantity);
    }

    public function test_checkout_clears_cart_after_success(): void
    {
        Redis::setex("cart:{$this->user->id}", 3600, json_encode([
            $this->product->id => 1,
        ]));

        $this->service->checkoutFromCart($this->user);

        $this->assertNull(Redis::get("cart:{$this->user->id}"));
    }

    public function test_advance_status_valid_transition(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST1',
            'user_id' => $this->user->id,
            'status' => 'paid',
            'subtotal' => 100, 'tax' => 6, 'shipping' => 0, 'total' => 106,
            'currency' => 'USD',
            'paid_at' => now(),
        ]);

        $updated = $this->service->advanceStatus($order, 'shipped');

        $this->assertEquals('shipped', $updated->status);
        $this->assertNotNull($updated->shipped_at);
    }

    public function test_advance_status_invalid_transition(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST2',
            'user_id' => $this->user->id,
            'status' => 'pending',
            'subtotal' => 100, 'tax' => 6, 'shipping' => 0, 'total' => 106,
            'currency' => 'USD',
        ]);

        $this->expectException(RuntimeException::class);
        $this->service->advanceStatus($order, 'delivered');  // cannot jump
    }
}