<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayPalController extends Controller
{
    public function __construct(protected PayPalService $payPalService)
    {
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::with('customer')->findOrFail($data['order_id']);
        $this->ensureOrderOwnership($order);

        if ($order->payment_method !== 'paypal') {
            return response()->json([
                'error' => 'Order is not configured for PayPal.',
            ], 422);
        }

        $paypalOrder = $this->payPalService->createOrder($order);
        $approvalLink = $this->extractLink($paypalOrder, 'approve');

        Payment::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => 'paypal'],
            [
                'gateway_payment_id' => $paypalOrder['id'],
                'amount' => $order->total,
                'currency' => 'USD',
                'status' => Payment::STATUS_PENDING,
            ]
        );

        return response()->json([
            'success' => true,
            'paypal_order_id' => $paypalOrder['id'],
            'approval_url' => $approvalLink,
        ]);
    }

    public function capture(Request $request)
    {
        $data = $request->validate([
            'paypal_order_id' => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $capture = $this->payPalService->captureOrder($data['paypal_order_id']);
        $payment = Payment::where('gateway_payment_id', $data['paypal_order_id'])->first();

        if (!$payment && isset($data['order_id'])) {
            $payment = Payment::firstOrCreate(
                ['order_id' => $data['order_id'], 'gateway' => 'paypal'],
                [
                    'gateway_payment_id' => $data['paypal_order_id'],
                    'amount' => optional(Order::find($data['order_id']))?->total ?? 0,
                    'currency' => 'USD',
                    'status' => Payment::STATUS_PENDING,
                ]
            );
        }

        if (!$payment) {
            return response()->json(['error' => 'Payment record not found.'], 404);
        }

        $order = $payment->order()->first();
        $this->ensureOrderOwnership($order);

        $status = strtoupper(Arr::get($capture, 'status', ''));
        $isCompleted = $status === 'COMPLETED';

        if ($isCompleted) {
            $payment->markAsCompleted();
            $order->markAsPaid();
            $order->update(['status' => Order::STATUS_SUCCESS]);
        } else {
            $payment->markAsFailed();
            $order->update(['status' => Order::STATUS_FAILED]);
        }

        return response()->json([
            'success' => $isCompleted,
            'status' => $status,
            'details' => $capture,
        ]);
    }

    protected function ensureOrderOwnership(?Order $order): void
    {
        if (!$order || $order->customer?->user_id !== auth()->id()) {
            abort(403);
        }
    }

    protected function extractLink(array $payload, string $rel): ?string
    {
        $links = $payload['links'] ?? [];
        $match = collect($links)->firstWhere('rel', $rel);

        return $match['href'] ?? null;
    }
}



