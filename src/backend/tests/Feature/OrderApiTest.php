<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        Redis::del("cart:{$this->user->id}");

        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product', 'slug' => 'tp', 'sku' => 'TP-001',
            'price' => 50, 'stock_quantity' => 20,
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_checkout_requires_auth(): void
    {
        $this->postJson('/api/checkout')->assertStatus(401);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/checkout')
            ->assertStatus(422)
            ->assertJson(['message' => 'Cart is empty.']);
    }

    public function test_checkout_creates_order_from_cart(): void
    {
        // Add item to cart
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/checkout');

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id', 'order_number', 'status', 'total', 'items']]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_checkout_preserves_price_snapshot(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/checkout');

        // Change product price after order
        $this->product->update(['price' => 999]);

        $orderId = $response->json('data.id');
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'unit_price' => 50,  // original price
        ]);
    }

    public function test_can_list_own_orders(): void
    {
        // Seed 2 orders
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->withHeaders($this->headers())->postJson('/api/checkout');

        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->withHeaders($this->headers())->postJson('/api/checkout');

        $this->withHeaders($this->headers())
            ->getJson('/api/orders')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_cannot_view_other_users_order(): void
    {
        $other = User::factory()->create();
        Redis::del("cart:{$other->id}");

        // Other user creates order
        $this->actingAs($other, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $checkout = $this->actingAs($other, 'sanctum')
            ->postJson('/api/checkout');

        $orderId = $checkout->json('data.id');
        $this->assertNotNull($orderId, 'Checkout must return an order id');

        // Current user tries to view other user's order
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$orderId}");

        // Debug output kalau gagal
        if ($response->status() !== 403) {
            dump([
                'current_user_id' => $this->user->id,
                'other_user_id' => $other->id,
                'order_id' => $orderId,
                'order_user_id' => \App\Models\Order::find($orderId)?->user_id,
                'response_status' => $response->status(),
            ]);
        }

        $response->assertStatus(403);
    }

    public function test_admin_can_advance_order_status(): void
    {
        // Create order as customer
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $checkout = $this->withHeaders($this->headers())->postJson('/api/checkout');
        $orderId = $checkout->json('data.id');

        // Manually set to paid
        \App\Models\Order::where('id', $orderId)->update(['status' => 'paid', 'paid_at' => now()]);

        // Admin acts
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/orders/{$orderId}/status", ['status' => 'shipped']);

        if ($response->status() === 403) {
            // Debug: dump response body
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');
    }

    public function test_customer_cannot_advance_order_status(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $checkout = $this->withHeaders($this->headers())->postJson('/api/checkout');
        $orderId = $checkout->json('data.id');

        $this->withHeaders($this->headers())
            ->putJson("/api/orders/{$orderId}/status", ['status' => 'shipped'])
            ->assertStatus(403);
    }

    public function test_invalid_status_transition_rejected(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);
        $checkout = $this->withHeaders($this->headers())->postJson('/api/checkout');
        $orderId = $checkout->json('data.id');

        // Order is 'pending', try to advance to 'delivered' (invalid)
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $admin->refresh();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/orders/{$orderId}/status", ['status' => 'delivered'])
            ->assertStatus(422);
    }
}