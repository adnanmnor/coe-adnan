<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    private function key(int $userId): string
    {
        return "cart:{$userId}";
    }

    private function getCart(int $userId): array
    {
        $data = Redis::get($this->key($userId));

        return $data ? json_decode($data, true) : [];
    }

    private function saveCart(int $userId, array $cart): void
    {
        Redis::setex($this->key($userId), 60 * 60 * 24 * 7, json_encode($cart));
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cart = $this->getCart($userId);

        if (empty($cart)) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        $items = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $products->get($productId);
            if (! $product) continue;

            $subtotal = (float) $product->price * $quantity;
            $total += $subtotal;

            $items[] = [
                'product_id' => (int) $productId,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'quantity' => (int) $quantity,
                'subtotal' => $subtotal,
            ];
        }

        return response()->json([
            'items' => $items,
            'total' => round($total, 2),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $userId = $request->user()->id;
        $cart = $this->getCart($userId);

        $pid = (string) $data['product_id'];
        $cart[$pid] = ($cart[$pid] ?? 0) + $data['quantity'];

        $this->saveCart($userId, $cart);

        return $this->index($request);
    }

    public function updateItem(Request $request, int $productId): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $userId = $request->user()->id;
        $cart = $this->getCart($userId);
        $pid = (string) $productId;

        if ($data['quantity'] === 0) {
            unset($cart[$pid]);
        } else {
            $cart[$pid] = $data['quantity'];
        }

        $this->saveCart($userId, $cart);

        return $this->index($request);
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $userId = $request->user()->id;
        $cart = $this->getCart($userId);
        unset($cart[(string) $productId]);
        $this->saveCart($userId, $cart);

        return $this->index($request);
    }

    public function clear(Request $request): JsonResponse
    {
        Redis::del($this->key($request->user()->id));

        return response()->json(['items' => [], 'total' => 0]);
    }
}