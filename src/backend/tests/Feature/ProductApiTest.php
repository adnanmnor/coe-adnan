<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::factory()->create(['role' => 'admin']);
        return $admin->createToken('test')->plainTextToken;
    }

    private function customerToken(): string
    {
        $customer = User::factory()->create(['role' => 'customer']);
        return $customer->createToken('test')->plainTextToken;
    }

    public function test_index_lists_products(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Phone',
            'slug' => 'phone',
            'sku' => 'PH-001',
            'price' => 100,
            'stock_quantity' => 10,
        ]);

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_supports_search(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'iPhone',
            'slug' => 'iphone', 'sku' => 'IP-001',
            'price' => 999, 'stock_quantity' => 5,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Samsung',
            'slug' => 'samsung', 'sku' => 'SS-001',
            'price' => 899, 'stock_quantity' => 5,
        ]);

        $response = $this->getJson('/api/products?search=iPhone');
        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_show_returns_product_with_category(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Phone', 'slug' => 'phone', 'sku' => 'PH-001',
            'price' => 100, 'stock_quantity' => 10,
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.sku', 'PH-001')
            ->assertJsonPath('data.category.slug', 'elec');
    }

    public function test_admin_can_create_product(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken()])
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Laptop',
                'sku' => 'LP-001',
                'price' => 1500,
                'stock_quantity' => 5,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.sku', 'LP-001');
    }

    public function test_customer_cannot_create_product(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->customerToken()])
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Laptop',
                'sku' => 'LP-001',
                'price' => 1500,
                'stock_quantity' => 5,
            ])
            ->assertStatus(403);
    }

    public function test_guest_cannot_create_product(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Laptop', 'sku' => 'LP-001',
            'price' => 1500, 'stock_quantity' => 5,
        ])->assertStatus(401);
    }

    public function test_admin_can_update_product(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Old', 'slug' => 'old', 'sku' => 'OLD-1',
            'price' => 100, 'stock_quantity' => 5,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken()])
            ->putJson("/api/products/{$product->id}", ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_admin_can_delete_product(): void
    {
        $category = Category::create(['name' => 'Elec', 'slug' => 'elec']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'X', 'slug' => 'x', 'sku' => 'X-1',
            'price' => 100, 'stock_quantity' => 5,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken()])
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminToken()])
            ->postJson('/api/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'sku', 'price', 'category_id']);
    }
}