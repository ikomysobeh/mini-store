<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends Controller
{
    protected $cartService;
    protected $stripeService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
        $this->stripeService = new StripeService();
    }

    public function index(Request $request)
    {
        $query = Order::with(['customer.user', 'items']);

        if ($request->search) {
            $query->whereHas('customer.user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            })->orWhere('id', 'like', '%' . $request->search . '%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_donation')) {
            $query->where('is_donation', $request->is_donation);
        }

        $orders = $query->latest()->paginate(15);

        // Calculate stats
        $stats = $this->calculateStats();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'is_donation']),
            'statuses' => Order::getStatuses(),
            'stats' => $stats, // Add this line
        ]);
    }
    private function calculateStats()
    {
        $baseQuery = Order::query();

        return [
            'total' => $baseQuery->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'processing' => (clone $baseQuery)->where('status', 'processing')->count(),
            'shipped' => (clone $baseQuery)->where('status', 'shipped')->count(),
            'delivered' => (clone $baseQuery)->where('status', 'delivered')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'total_revenue' => (clone $baseQuery)->where('is_donation', false)->sum('total'),
            'donations_total' => (clone $baseQuery)->where('is_donation', true)->sum('total'),
        ];
    }


    public function show(Order $order)
    {
        // ENHANCED: Load with variant relationships and product data
        $order->load([
            'customer.user',
            'items.product',
            'items.variant.color',  // Load variant with color
            'items.variant.size',   // Load variant with size
            'payments'
        ]);

        // Process image URLs and prepare variant data for display
        $order->items->each(function ($item) {
            // Add product image URL
            if ($item->product && $item->product->image) {
                $item->product->image_url = asset('storage/' . $item->product->image);
            }

            // The variant_details attribute will be computed automatically
            // from the model's getVariantDetailsAttribute method
        });

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'statuses' => Order::getStatuses(),
        ]);
    }    public function update(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        if ($request->status === 'shipped') {
            $order->update(['ready_at' => now()]);
        }

        return back()->with('success', 'Order updated successfully');
    }

    public function export(Request $request)
    {
        try {
            $filters = $request->only(['search', 'status', 'type', 'date_range']);
            $includeOrderItems = $request->boolean('include_order_items', true);
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "orders_export_{$timestamp}.xlsx";

            return Excel::download(new OrdersExport($filters, $includeOrderItems), $filename);
        } catch (Exception $e) {
            Log::error('Orders export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please try again.');
        }
    }

// Enhanced export selected orders
    public function exportSelected(Request $request)
    {
        try {
            $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'exists:orders,id',
                'include_order_items' => 'boolean'
            ]);

            $includeOrderItems = $request->boolean('include_order_items', true);
            $timestamp = now()->format('Y-m-d_H-i-s');
            $count = count($request->order_ids);
            $filename = "selected_orders_{$count}_{$timestamp}.xlsx";

            $filters = ['selected_ids' => $request->order_ids];
            return Excel::download(new OrdersExport($filters, $includeOrderItems), $filename);
        } catch (Exception $e) {
            Log::error('Selected orders export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please try again.');
        }
    }
}
