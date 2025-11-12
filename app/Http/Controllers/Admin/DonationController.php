<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class DonationController extends Controller
{
    /**
     * Display a listing of donations
     */
    public function index(Request $request)
    {
        $query = Donation::query();

        // Search filter
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('id', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->date_range) {
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', now()->subYear());
                    break;
            }
        }

        // Amount range filter
        if ($request->min_amount) {
            $query->where('value', '>=', $request->min_amount);
        }
        if ($request->max_amount) {
            $query->where('value', '<=', $request->max_amount);
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $allowedSorts = ['id', 'name', 'value', 'status', 'created_at', 'paid_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest();
        }

        $donations = $query->paginate(15);

        // Calculate stats
        $stats = $this->calculateStats();

        return Inertia::render('Admin/Donations/Index', [
            'donations' => $donations,
            'filters' => $request->only(['search', 'status', 'date_range', 'min_amount', 'max_amount', 'sort', 'direction']),
            'statuses' => Donation::getStatuses(),
            'stats' => $stats,
        ]);
    }

    /**
     * Display the specified donation
     */
    public function show(Donation $donation)
    {
        return Inertia::render('Admin/Donations/Show', [
            'donation' => $donation,
        ]);
    }

    /**
     * Remove the specified donation
     */
    public function destroy(Donation $donation)
    {
        // Only allow deletion of failed or pending donations
        if ($donation->status === Donation::STATUS_COMPLETED) {
            return back()->withErrors(['error' => 'Cannot delete completed donations.']);
        }

        $donation->delete();

        return redirect()->route('admin.donations.index')
            ->with('success', 'Donation deleted successfully.');
    }

    /**
     * Calculate donation statistics
     */
    private function calculateStats()
    {
        $baseQuery = Donation::query();

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', Donation::STATUS_PENDING)->count(),
            'completed' => (clone $baseQuery)->where('status', Donation::STATUS_COMPLETED)->count(),
            'failed' => (clone $baseQuery)->where('status', Donation::STATUS_FAILED)->count(),
            
            // Revenue stats (only completed)
            'total_revenue' => (clone $baseQuery)
                ->where('status', Donation::STATUS_COMPLETED)
                ->sum('value'),
            
            // Time-based stats
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'this_week' => (clone $baseQuery)->where('created_at', '>=', now()->subWeek())->count(),
            'this_month' => (clone $baseQuery)->where('created_at', '>=', now()->subMonth())->count(),
            'this_year' => (clone $baseQuery)->where('created_at', '>=', now()->subYear())->count(),
            
            // Revenue time-based (only completed)
            'today_revenue' => (clone $baseQuery)
                ->where('status', Donation::STATUS_COMPLETED)
                ->whereDate('created_at', today())
                ->sum('value'),
            'this_month_revenue' => (clone $baseQuery)
                ->where('status', Donation::STATUS_COMPLETED)
                ->where('created_at', '>=', now()->subMonth())
                ->sum('value'),
        ];
    }

    /**
     * Bulk delete donations
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'donation_ids' => 'required|array',
            'donation_ids.*' => 'exists:donations,id',
        ]);

        $donations = Donation::whereIn('id', $request->donation_ids)->get();

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($donations as $donation) {
            if ($donation->status === Donation::STATUS_COMPLETED) {
                $skippedCount++;
                continue;
            }

            $donation->delete();
            $deletedCount++;
        }

        $message = "Deleted {$deletedCount} donations.";
        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} completed donations.";
        }

        return back()->with('success', $message);
    }
}
