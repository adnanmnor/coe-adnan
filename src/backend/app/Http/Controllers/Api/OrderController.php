<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items', 'payments'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'admin' && (int) $order->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $order->load(['items', 'payments', 'user:id,name,email']);

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function checkout(Request $request): JsonResponse
    {
        try {
            $order = $this->orders->checkoutFromCart($request->user());

            return response()->json([
                'message' => 'Order created. Processing asynchronously.',
                'data' => new OrderResource($order->load(['items', 'payments'])),
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:shipped,delivered,cancelled'],
        ]);

        try {
            $order = $this->orders->advanceStatus($order, $data['status']);

            return response()->json([
                'message' => 'Order status updated.',
                'data' => new OrderResource($order),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
