<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::find($this->orderId);

        if (! $order) {
            Log::warning("ProcessOrderJob: order #{$this->orderId} not found");
            return;
        }

        Log::info("ProcessOrderJob: processing order {$order->order_number}");

        $payment = Payment::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'method' => 'mock',
            'amount' => $order->total,
        ]);

        // Simulate payment processing delay
        sleep(2);

        // Mock: fail if total > 100000 (untuk demo retry/dead-letter)
        $shouldFail = $order->total > 100000;

        if ($shouldFail) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Mock gateway decline (amount too high)',
            ]);

            throw new \RuntimeException("Mock payment failed for order {$order->order_number}");
        }

        $payment->update([
            'status' => 'success',
            'transaction_id' => 'MOCK-' . strtoupper(Str::random(12)),
        ]);

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Log::info("ProcessOrderJob: order {$order->order_number} paid successfully");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("ProcessOrderJob failed for order #{$this->orderId}: {$exception->getMessage()}");

        Order::where('id', $this->orderId)->update(['status' => 'failed']);
    }
}