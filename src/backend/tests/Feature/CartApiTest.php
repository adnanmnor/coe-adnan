<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CartApiTest extends TestCase
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

        // Clear Redis cart
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

    public function test_cart_requires_auth(): void
    {
        $this->getJson('/api/cart')->assertStatus(401);
    }

    public function test_empty_cart_returns_zero(): void
    {
        $this->withHeaders($this->headers())
            ->getJson('/api/cart')
            ->assertStatus(200)
            ->assertJson(['items' => [], 'total' => 0]);
    }

    public function test_add_item_to_cart(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('total', 100)
            ->assertJsonPath('items.0.product_id', $this->product->id)
            ->assertJsonPath('items.0.quantity', 2);
    }

    public function test_update_item_quantity(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $this->withHeaders($this->headers())
            ->putJson("/api/cart/items/{$this->product->id}", ['quantity' => 5])
            ->assertStatus(200)
            ->assertJsonPath('total', 250);
    }

    public function test_remove_item(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        $this->withHeaders($this->headers())
            ->deleteJson("/api/cart/items/{$this->product->id}")
            ->assertStatus(200)
            ->assertJson(['items' => [], 'total' => 0]);
    }

    public function test_clear_cart(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 3,
            ]);

        $this->withHeaders($this->headers())
            ->deleteJson('/api/cart')
            ->assertStatus(200)
            ->assertJson(['items' => [], 'total' => 0]);
    }

    public function test_add_validates_product_exists(): void
    {
        $this->withHeaders($this->headers())
            ->postJson('/api/cart/items', [
                'product_id' => 99999,
                'quantity' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }
}