<?php
// app/Http/Controllers/Admin/ProductVariantController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Color;
use App\Models\Size;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductVariant::with(['product', 'color', 'size']);

        // Search functionality
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('sku', 'like', '%' . $request->search . '%')
                    ->orWhereHas('product', function($pq) use ($request) {
                        $pq->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('color', function($cq) use ($request) {
                        $cq->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('size', function($sq) use ($request) {
                        $sq->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Product filter
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Color filter
        if ($request->color_id) {
            $query->where('color_id', $request->color_id);
        }

        // Size filter
        if ($request->size_id) {
            $query->where('size_id', $request->size_id);
        }

        // Stock filter
        if ($request->stock_status) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('stock', '>', 0);
                    break;
                case 'low_stock':
                    $query->where('stock', '>', 0)->where('stock', '<=', 10);
                    break;
                case 'out_of_stock':
                    $query->where('stock', 0);
                    break;
            }
        }

        // Status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $allowedSorts = ['sku', 'stock', 'price_adjustment', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        }

        $variants = $query->paginate(15);

        // Transform variants to include computed fields
        $variants->getCollection()->transform(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'price_adjustment' => $variant->price_adjustment,
                'final_price' => $variant->final_price,
                'is_active' => $variant->is_active,
                'created_at' => $variant->created_at,
                'product' => $variant->product,
                'color' => $variant->color,
                'size' => $variant->size,
                'display_name' => $variant->display_name,
            ];
        });

        return Inertia::render('Admin/ProductVariants/Index', [
            'variants' => $variants,
            'filters' => $request->only(['search', 'product_id', 'color_id', 'size_id', 'stock_status', 'is_active', 'sort', 'direction']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name']),
            'colors' => Color::active()->ordered()->get(['id', 'name', 'hex_code']),
            'sizes' => Size::active()->ordered()->get(['id', 'name', 'category_type']),
        ]);
    }

    public function show(ProductVariant $variant)
    {
        $variant->load(['product.category', 'color', 'size', 'cartItems', 'orderItems']);

        return Inertia::render('Admin/ProductVariants/Show', [
            'variant' => $variant,
            'stats' => [
                'total_cart_items' => $variant->cartItems->count(),
                'total_orders' => $variant->orderItems->count(),
                'total_sold' => $variant->orderItems->sum('quantity'),
            ],
        ]);
    }

    public function edit(ProductVariant $variant)
    {
        $variant->load(['product', 'color', 'size']);

        return Inertia::render('Admin/ProductVariants/Edit', [
            'variant' => $variant,
        ]);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:255|unique:product_variants,sku,' . $variant->id,
            'stock' => 'required|integer|min:0',
            'price_adjustment' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $variant->update($validated);

        return redirect()->route('admin.product-variants.index')
            ->with('success', 'Variant updated successfully');
    }

    public function destroy(ProductVariant $variant)
    {
        // Check if variant has cart items or order items
        if ($variant->cartItems()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete variant that exists in shopping carts']);
        }

        if ($variant->orderItems()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete variant that has been ordered']);
        }

        $variant->delete();

        return redirect()->route('admin.product-variants.index')
            ->with('success', 'Variant deleted successfully');
    }

    // Bulk stock update
    public function bulkUpdateStock(Request $request)
    {
        $request->validate([
            'variants' => 'required|array',
            'variants.*.id' => 'required|exists:product_variants,id',
            'variants.*.stock' => 'required|integer|min:0'
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->variants as $variantData) {
                ProductVariant::where('id', $variantData['id'])
                    ->update(['stock' => $variantData['stock']]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Stock updated successfully']);
    }

    // Bulk actions
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'variant_ids' => 'required|array',
            'variant_ids.*' => 'exists:product_variants,id'
        ]);

        $variants = ProductVariant::whereIn('id', $request->variant_ids);

        switch ($request->action) {
            case 'activate':
                $variants->update(['is_active' => true]);
                $message = 'Variants activated successfully';
                break;
            case 'deactivate':
                $variants->update(['is_active' => false]);
                $message = 'Variants deactivated successfully';
                break;
            case 'delete':
                // Check for cart items or order items
                $hasCartItems = $variants->whereHas('cartItems')->exists();
                $hasOrderItems = $variants->whereHas('orderItems')->exists();

                if ($hasCartItems || $hasOrderItems) {
                    return back()->withErrors(['error' => 'Cannot delete variants that exist in carts or have been ordered']);
                }

                $variants->delete();
                $message = 'Variants deleted successfully';
                break;
        }

        return back()->with('success', $message);
    }

    // Generate variants for a product
    public function generateVariants(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'color_ids' => 'required|array',
            'color_ids.*' => 'exists:colors,id',
            'size_ids' => 'required|array',
            'size_ids.*' => 'exists:sizes,id',
            'default_stock' => 'integer|min:0',
            'default_price_adjustment' => 'numeric',
        ]);

        $product = Product::findOrFail($request->product_id);
        $colors = Color::whereIn('id', $request->color_ids)->get();
        $sizes = Size::whereIn('id', $request->size_ids)->get();

        $createdVariants = 0;
        $skippedVariants = 0;

        DB::transaction(function () use ($product, $colors, $sizes, $request, &$createdVariants, &$skippedVariants) {
            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    // Check if variant already exists
                    $exists = ProductVariant::where('product_id', $product->id)
                        ->where('color_id', $color->id)
                        ->where('size_id', $size->id)
                        ->exists();

                    if ($exists) {
                        $skippedVariants++;
                        continue;
                    }

                    // Create new variant
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'color_id' => $color->id,
                        'size_id' => $size->id,
                        'stock' => $request->default_stock ?? 0,
                        'price_adjustment' => $request->default_price_adjustment ?? 0,
                        'is_active' => true,
                        // SKU will be auto-generated by the model
                    ]);

                    $createdVariants++;
                }
            }
        });

        $message = "Created {$createdVariants} variants";
        if ($skippedVariants > 0) {
            $message .= ", skipped {$skippedVariants} existing variants";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'created' => $createdVariants,
            'skipped' => $skippedVariants
        ]);
    }
}
