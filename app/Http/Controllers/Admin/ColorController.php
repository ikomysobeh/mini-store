<?php
// app/Http/Controllers/Admin/ColorController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ColorController extends Controller
{
    public function index(Request $request)
    {
        $query = Color::query();

        // Search functionality
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('hex_code', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $sortBy = $request->get('sort', 'sort_order');
        $direction = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'hex_code', 'is_active', 'sort_order', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->ordered();
        }

        $colors = $query->paginate(15);

        return Inertia::render('Admin/Colors/Index', [
            'colors' => $colors,
            'filters' => $request->only(['search', 'is_active', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Colors/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:colors,name',
            'hex_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/|unique:colors,hex_code',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? Color::max('sort_order') + 1;

        Color::create($validated);

        return redirect()->route('admin.colors.index')
            ->with('success', 'Color created successfully');
    }

    public function show(Color $color)
    {
        $color->load(['variants.product']);

        return Inertia::render('Admin/Colors/Show', [
            'color' => $color,
            'stats' => [
                'total_variants' => $color->variants->count(),
                'total_products' => $color->products->count(),
                'total_stock' => $color->variants->sum('stock'),
            ],
        ]);
    }

    public function edit(Color $color)
    {
        return Inertia::render('Admin/Colors/Edit', [
            'color' => $color,
        ]);
    }

    public function update(Request $request, Color $color)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:colors,name,' . $color->id,
            'hex_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/|unique:colors,hex_code,' . $color->id,
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $color->update($validated);

        return redirect()->route('admin.colors.index')
            ->with('success', 'Color updated successfully');
    }

    public function destroy(Color $color)
    {
        // Check if color has variants
        if ($color->variants()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete color that has product variants']);
        }

        $color->delete();

        return redirect()->route('admin.colors.index')
            ->with('success', 'Color deleted successfully');
    }

    // Bulk actions
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'color_ids' => 'required|array',
            'color_ids.*' => 'exists:colors,id'
        ]);

        $colors = Color::whereIn('id', $request->color_ids);

        switch ($request->action) {
            case 'activate':
                $colors->update(['is_active' => true]);
                $message = 'Colors activated successfully';
                break;
            case 'deactivate':
                $colors->update(['is_active' => false]);
                $message = 'Colors deactivated successfully';
                break;
            case 'delete':
                // Check for variants
                $hasVariants = $colors->whereHas('variants')->exists();
                if ($hasVariants) {
                    return back()->withErrors(['error' => 'Cannot delete colors that have product variants']);
                }
                $colors->delete();
                $message = 'Colors deleted successfully';
                break;
        }

        return back()->with('success', $message);
    }

    // Update sort order
    public function updateSortOrder(Request $request)
    {
        $request->validate([
            'colors' => 'required|array',
            'colors.*.id' => 'required|exists:colors,id',
            'colors.*.sort_order' => 'required|integer|min:0'
        ]);

        foreach ($request->colors as $colorData) {
            Color::where('id', $colorData['id'])
                ->update(['sort_order' => $colorData['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
