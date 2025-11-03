<?php
// app/Http/Controllers/Admin/SizeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SizeController extends Controller
{
    public function index(Request $request)
    {
        $query = Size::query();

        // Search functionality
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('category_type', 'like', '%' . $request->search . '%');
            });
        }

        // Category filter
        if ($request->category_type) {
            $query->where('category_type', $request->category_type);
        }

        // Status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $sortBy = $request->get('sort', 'sort_order');
        $direction = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'category_type', 'is_active', 'sort_order', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->ordered();
        }

        $sizes = $query->paginate(15);

        return Inertia::render('Admin/Sizes/Index', [
            'sizes' => $sizes,
            'filters' => $request->only(['search', 'category_type', 'is_active', 'sort', 'direction']),
            'categoryTypes' => Size::getCategoryTypes(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Sizes/Create', [
            'categoryTypes' => Size::getCategoryTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_type' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Check uniqueness within category
        $exists = Size::where('name', $validated['name'])
            ->where('category_type', $validated['category_type'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Size already exists in this category']);
        }

        $validated['sort_order'] = $validated['sort_order'] ??
            Size::where('category_type', $validated['category_type'])->max('sort_order') + 1;

        Size::create($validated);

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size created successfully');
    }

    public function show(Size $size)
    {
        $size->load(['variants.product']);

        return Inertia::render('Admin/Sizes/Show', [
            'size' => $size,
            'stats' => [
                'total_variants' => $size->variants->count(),
                'total_products' => $size->products->count(),
                'total_stock' => $size->variants->sum('stock'),
            ],
        ]);
    }

    public function edit(Size $size)
    {
        return Inertia::render('Admin/Sizes/Edit', [
            'size' => $size,
            'categoryTypes' => Size::getCategoryTypes(),
        ]);
    }

    public function update(Request $request, Size $size)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_type' => 'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Check uniqueness within category (excluding current record)
        $exists = Size::where('name', $validated['name'])
            ->where('category_type', $validated['category_type'])
            ->where('id', '!=', $size->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Size already exists in this category']);
        }

        $size->update($validated);

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size updated successfully');
    }

    public function destroy(Size $size)
    {
        // Check if size has variants
        if ($size->variants()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete size that has product variants']);
        }

        $size->delete();

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size deleted successfully');
    }

    // Bulk actions
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'size_ids' => 'required|array',
            'size_ids.*' => 'exists:sizes,id'
        ]);

        $sizes = Size::whereIn('id', $request->size_ids);

        switch ($request->action) {
            case 'activate':
                $sizes->update(['is_active' => true]);
                $message = 'Sizes activated successfully';
                break;
            case 'deactivate':
                $sizes->update(['is_active' => false]);
                $message = 'Sizes deactivated successfully';
                break;
            case 'delete':
                // Check for variants
                $hasVariants = $sizes->whereHas('variants')->exists();
                if ($hasVariants) {
                    return back()->withErrors(['error' => 'Cannot delete sizes that have product variants']);
                }
                $sizes->delete();
                $message = 'Sizes deleted successfully';
                break;
        }

        return back()->with('success', $message);
    }

    // Update sort order
    public function updateSortOrder(Request $request)
    {
        $request->validate([
            'sizes' => 'required|array',
            'sizes.*.id' => 'required|exists:sizes,id',
            'sizes.*.sort_order' => 'required|integer|min:0'
        ]);

        foreach ($request->sizes as $sizeData) {
            Size::where('id', $sizeData['id'])
                ->update(['sort_order' => $sizeData['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
