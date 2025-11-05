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

// In App/Http/Controllers/Admin/OrderController.php
// REPLACE the existing index method

    public function index(Request $request)
    {
        // ENHANCED: Load beneficiary relationship
        $query = Order::with(['customer.user', 'items', 'beneficiary']); // NEW: Added 'beneficiary'

        // ENHANCED: Updated search to include beneficiary
        if ($request->search) {
            $query->where(function($q) use ($request) {
                // Search in customer/user data
                $q->whereHas('customer.user', function($subQ) use ($request) {
                    $subQ->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                })
                    // Search in order ID
                    ->orWhere('id', 'like', '%' . $request->search . '%')
                    // SIMPLIFIED: Search only in existing beneficiary fields
                    ->orWhereHas('beneficiary', function($subQ) use ($request) {
                        $subQ->where('first_name', 'like', '%' . $request->search . '%')
                            ->orWhere('last_name', 'like', '%' . $request->search . '%')
                            ->orWhere('organization_name', 'like', '%' . $request->search . '%');
                        // REMOVED: email and relationship searches
                    });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_donation')) {
            $query->where('is_donation', $request->is_donation);
        }

        // NEW: Filter by beneficiary presence
        if ($request->has('has_beneficiary')) {
            if ($request->has_beneficiary === '1') {
                $query->whereNotNull('beneficiary_id');
            } elseif ($request->has_beneficiary === '0') {
                $query->whereNull('beneficiary_id');
            }
        }

        $orders = $query->latest()->paginate(15);

        // ENHANCED: Calculate stats with beneficiary data
        $stats = $this->calculateStats();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'is_donation', 'has_beneficiary']), // NEW: Added has_beneficiary
            'statuses' => Order::getStatuses(),
            'stats' => $stats,
        ]);
    }
// In App/Http/Controllers/Admin/OrderController.php
// REPLACE the existing calculateStats method

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

            // NEW: Beneficiary-related stats
            'donations_with_beneficiary' => (clone $baseQuery)->where('is_donation', true)->whereNotNull('beneficiary_id')->count(),
            'donations_without_beneficiary' => (clone $baseQuery)->where('is_donation', true)->whereNull('beneficiary_id')->count(),
            'total_beneficiaries' => \App\Models\DonationBeneficiary::count(),
        ];
    }


// In App/Http/Controllers/Admin/OrderController.php
// REPLACE the existing show method

    public function show(Order $order)
    {
        // ENHANCED: Load beneficiary relationship
        $order->load([
            'customer.user',
            'items.product',
            'items.variant.color',
            'items.variant.size',
            'beneficiary', // NEW: Load beneficiary data
            'payments'
        ]);

        // Process image URLs and prepare variant data for display
        $order->items->each(function ($item) {
            if ($item->product && $item->product->image) {
                $item->product->image_url = asset('storage/' . $item->product->image);
            }
        });

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
            'statuses' => Order::getStatuses(),
        ]);
    }

    public function update(Request $request, Order $order)
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
