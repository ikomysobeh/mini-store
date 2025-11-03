<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{


    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', '!=', 'cancelled')->sum('total'),
            'total_donations' => Order::donations()->sum('total'),
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'pending_orders' => Order::status('pending')->count(),
        ];

        $recentOrders = Order::with(['customer.user', 'items'])
            ->latest()
            ->take(10)
            ->get();

        $topProducts = Product::select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->groupBy('products.id')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
        ]);
    }
}
