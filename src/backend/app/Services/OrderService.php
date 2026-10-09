<?php

namespace App\Services;

use App\Jobs\ProcessOrderJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    /**
     * Convert user's Redis cart into an Order. Returns the new Order.
     */
    public function checkoutFromCart(User $user): Order
    {
        $cartKey = "cart:{$user->id}";
        $cartRaw = Redis::get($cartKey);

        if (! $cartRaw) {
            throw new RuntimeException('Cart is empty.');
        }

        $cart = json_decode($cartRaw, true);

        if (empty($cart)) {
            throw new RuntimeException('Cart is empty.');
        }

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        if ($products->isEmpty()) {
            throw new RuntimeException('No valid products in cart.');
        }

        return DB::transaction(function () use ($user, $cart, $products, $cartKey) {
            $subtotal = 0;
            $lines = [];

            foreach ($cart as $productId => $qty) {
                $product = $products->get($productId);
                if (! $product) continue;

                $qty = (int) $qty;
                if ($qty < 1) continue;

                $lineTotal = (float) $product->price * $qty;
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price' => (float) $product->price,
                    'quantity' => $qty,
                    'subtotal' => $lineTotal,
                ];
            }

            if (empty($lines)) {
                throw new RuntimeException('No valid items in cart.');
            }

            $tax = round($subtotal * 0.06, 2);
            $shipping = 0;
            $total = round($subtotal + $tax + $shipping, 2);

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'currency' => 'USD',
            ]);

            foreach ($lines as $line) {
                $line['order_id'] = $order->id;
                OrderItem::create($line);
            }

            Redis::del($cartKey);

            ProcessOrderJob::dispatch($order->id);

            Log::info("Order created: {$order->order_number}, dispatched ProcessOrderJob");

            return $order->fresh(['items', 'payments']);
        });
    }

    public function advanceStatus(Order $order, string $newStatus): Order
    {
        $allowed = [
            'pending' => ['paid', 'failed', 'cancelled'],
            'paid' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'failed' => ['pending'],
            'cancelled' => [],
        ];

        if (! in_array($newStatus, $allowed[$order->status] ?? [], true)) {
            throw new RuntimeException("Cannot transition from {$order->status} to {$newStatus}.");
        }

        $timestamps = [
            'paid' => 'paid_at',
            'shipped' => 'shipped_at',
            'delivered' => 'delivered_at',
        ];

        $data = ['status' => $newStatus];
        if (isset($timestamps[$newStatus])) {
            $data[$timestamps[$newStatus]] = now();
        }

        $order->update($data);

        return $order->fresh(['items', 'payments']);
    }
}