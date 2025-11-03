<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'variants.color', 'variants.size']);

            // Search filter
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('slug', 'like', '%' . $request->search . '%');
                });
            }

            // Category filter
            if ($request->category) {
                if (is_numeric($request->category)) {
                    $query->where('category_id', $request->category);
                } else {
                    $query->whereHas('category', function($q) use ($request) {
                        $q->where('slug', $request->category);
                    });
                }
            }

            // Status filter
            if ($request->status) {
                switch ($request->status) {
                    case 'active':
                        $query->where('is_active', true);
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'donatable':
                        $query->where('is_donatable', true);
                        break;
                    case 'not_donatable':
                        $query->where('is_donatable', false);
                        break;
                    case 'low_stock':
                        $query->where(function($q) {
                            $q->where('stock', '<=', 10)
                                ->orWhereHas('variants', function($vq) {
                                    $vq->where('stock', '<=', 10)->where('stock', '>', 0);
                                });
                        });
                        break;
                    case 'out_of_stock':
                        $query->where(function($q) {
                            $q->where('stock', 0)
                                ->orWhereHas('variants', function($vq) {
                                    $vq->where('stock', 0);
                                });
                        });
                        break;
                    case 'has_variants':
                        $query->has('variants');
                        break;
                    case 'no_variants':
                        $query->doesntHave('variants');
                        break;
                }
            }

            // Sorting
            $sortBy = $request->get('sort', 'created_at');
            $direction = $request->get('direction', 'desc');

            $allowedSorts = ['name', 'price', 'stock', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $direction);
            } else {
                $query->latest();
            }

            $products = $query->paginate(15);
            $categories = Category::active()->ordered()->get();

            // Transform products to include enhanced data
            $products->getCollection()->transform(function ($product) {
                $productArray = $product->toArray();

                // Add image URL
                if ($product->image) {
                    $productArray['image'] = asset('storage/' . $product->image);
                    $productArray['image_url'] = asset('storage/' . $product->image);
                }

                // Add variant information
                $productArray['has_variants'] = $product->variants->count() > 0;
                $productArray['variants_count'] = $product->variants->count();
                $productArray['total_stock'] = $product->variants->sum('stock') ?: $product->stock;

                if ($productArray['has_variants']) {
                    $productArray['available_colors'] = $product->variants->pluck('color.name')->unique()->values();
                    $productArray['available_sizes'] = $product->variants->pluck('size.name')->unique()->values();
                    $productArray['price_range'] = [
                        'min' => $product->variants->min(function($variant) use ($product) {
                            return $product->price + $variant->price_adjustment;
                        }),
                        'max' => $product->variants->max(function($variant) use ($product) {
                            return $product->price + $variant->price_adjustment;
                        })
                    ];
                }

                return $productArray;
            });

            // Calculate stats
            $stats = $this->calculateProductStats();

            return Inertia::render('Admin/Products/Index', [
                'products' => $products,
                'categories' => $categories,
                'filters' => $request->only(['search', 'category', 'status', 'sort', 'direction']),
                'stats' => $stats,
            ]);

        } catch (Exception $e) {
            Log::error('Products index failed: ' . $e->getMessage());

            return Inertia::render('Admin/Products/Index', [
                'products' => collect()->paginate(15),
                'categories' => [],
                'filters' => [],
                'stats' => [],
                'error' => 'Unable to load products. Please try again.'
            ]);
        }
    }

    // Export products to Excel
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['search', 'category', 'status']);
            $includeVariants = $request->boolean('include_variants', true);
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "products_export_{$timestamp}.xlsx";

            return Excel::download(new ProductsExport($filters, $includeVariants), $filename);
        } catch (Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please try again.');
        }
    }

// Enhanced export selected products
    public function exportSelected(Request $request)
    {
        try {
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id',
                'include_variants' => 'boolean'
            ]);

            $includeVariants = $request->boolean('include_variants', true);
            $timestamp = now()->format('Y-m-d_H-i-s');
            $count = count($request->product_ids);
            $filename = "selected_products_{$count}_{$timestamp}.xlsx";

            $filters = ['selected_ids' => $request->product_ids];
            return Excel::download(new ProductsExport($filters, $includeVariants), $filename);
        } catch (Exception $e) {
            Log::error('Selected export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed. Please try again.');
        }
    }

    private function calculateProductStats()
    {
        try {
            $totalProducts = Product::count();
            $activeProducts = Product::where('is_active', true)->count();
            $inactiveProducts = Product::where('is_active', false)->count();
            $donatableProducts = Product::where('is_donatable', true)->count();
            $productsWithVariants = Product::has('variants')->count();
            $productsWithoutVariants = Product::doesntHave('variants')->count();

            // Low stock calculation (considering variants)
            $lowStockProducts = Product::where(function($query) {
                $query->where('stock', '<=', 10)
                    ->where('stock', '>', 0)
                    ->doesntHave('variants');
            })->orWhereHas('variants', function($query) {
                $query->where('stock', '<=', 10)->where('stock', '>', 0);
            })->count();

            // Out of stock calculation
            $outOfStockProducts = Product::where(function($query) {
                $query->where('stock', 0)->doesntHave('variants');
            })->orWhereHas('variants', function($query) {
                $query->where('stock', 0);
            })->count();

            // Total value calculation
            $regularProductsValue = Product::where('is_active', true)
                ->doesntHave('variants')
                ->sum(DB::raw('price * stock'));

            $variantsValue = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
                ->where('products.is_active', true)
                ->where('product_variants.is_active', true)
                ->sum(DB::raw('(products.price + product_variants.price_adjustment) * product_variants.stock'));

            $totalValue = $regularProductsValue + $variantsValue;

            return [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'inactive' => $inactiveProducts,
                'donatable' => $donatableProducts,
                'with_variants' => $productsWithVariants,
                'without_variants' => $productsWithoutVariants,
                'low_stock' => $lowStockProducts,
                'out_of_stock' => $outOfStockProducts,
                'total_value' => $totalValue,
            ];
        } catch (Exception $e) {
            Log::error('Stats calculation failed: ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'donatable' => 0,
                'with_variants' => 0,
                'without_variants' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    public function create()
    {
        try {
            $categories = Category::active()->ordered()->get();

            return Inertia::render('Admin/Products/Create', [
                'categories' => $categories,
            ]);
        } catch (Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Unable to load product creation page. Please try again.');
        }
    }

    public function store(Request $request)
    {
        try {
            // DEBUG: Log what we're receiving
            Log::info('Store method - Request data:', [
                'all_data' => $request->all(),
                'has_colors' => $request->has('colors'),
                'colors_data' => $request->input('colors'),
                'has_sizes' => $request->has('sizes'),
                'sizes_data' => $request->input('sizes'),
                'has_variants' => $request->has('variants'),
                'variants_data' => $request->input('variants')
            ]);

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:products,name',
                ],
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'is_active' => 'nullable|boolean',
                'is_donatable' => 'nullable|boolean',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

                // FIXED: Properly validate colors, sizes, variants arrays
                'colors' => 'nullable|array',
                'colors.*' => 'nullable|array',
                'sizes' => 'nullable|array',
                'sizes.*' => 'nullable|array',
                'variants' => 'nullable|array',
                'variants.*' => 'nullable|array',
            ]);

            DB::transaction(function () use ($validated, $request) {
                // Generate unique slug
                $slug = $this->generateUniqueSlug($validated['name']);

                $productData = [
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'],
                    'price' => $validated['price'],
                    'stock' => $validated['stock'],
                    'category_id' => $validated['category_id'],
                    'is_active' => $request->boolean('is_active', true),
                    'is_donatable' => $request->boolean('is_donatable', false),
                ];

                // Handle image upload
                if ($request->hasFile('image')) {
                    try {
                        $productData['image'] = $request->file('image')->store('products', 'public');
                    } catch (Exception $e) {
                        Log::warning('Image upload failed during product creation: ' . $e->getMessage());
                    }
                }

                $product = Product::create($productData);
                Log::info('Product created with ID: ' . $product->id);

                // FIXED: Handle variants creation with proper data structure
                $colorsData = $request->input('colors', []);
                $sizesData = $request->input('sizes', []);
                $variantsData = $request->input('variants', []);

                if (!empty($colorsData) && !empty($sizesData) && !empty($variantsData)) {
                    Log::info('Creating variants', [
                        'colors_count' => count($colorsData),
                        'sizes_count' => count($sizesData),
                        'variants_count' => count($variantsData)
                    ]);

                    $this->createProductVariants($product, $colorsData, $sizesData, $variantsData);
                } else {
                    Log::info('No variants to create - using base product only');
                }
            });

            return redirect()->route('admin.products.index')
                ->with('success', 'Product created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Product creation validation failed:', $e->errors());
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please fix the validation errors and try again.');

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during product creation: ' . $e->getMessage());

            if (strpos($e->getMessage(), 'products_name_unique') !== false) {
                return back()
                    ->withErrors(['name' => 'A product with this name already exists.'])
                    ->withInput()
                    ->with('error', 'Product name must be unique.');
            }

            if (strpos($e->getMessage(), 'products_slug_unique') !== false) {
                return back()
                    ->withErrors(['name' => 'A product with a similar name already exists. Please choose a different name.'])
                    ->withInput()
                    ->with('error', 'Product name conflict detected.');
            }

            return back()
                ->with('error', 'A database error occurred. Please try again.')
                ->withInput();

        } catch (Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return back()
                ->with('error', 'Failed to create product. Please try again.')
                ->withInput();
        }
    }

// FIXED: Update the createProductVariants method signature and implementation
// FIXED: Update the createProductVariants method to handle existing colors/sizes
    private function createProductVariants($product, $colorsData, $sizesData, $variantsData)
    {
        try {
            Log::info('Starting variant creation', [
                'product_id' => $product->id,
                'colors_received' => count($colorsData),
                'sizes_received' => count($sizesData),
                'variants_received' => count($variantsData)
            ]);

            // FIXED: Create or get colors (check for existing ones first)
            $colorMapping = [];
            foreach ($colorsData as $colorData) {
                if (is_array($colorData)) {
                    // Check if it's a temporary ID (new color)
                    if (isset($colorData['id']) && strpos($colorData['id'], 'temp_') === 0) {
                        // FIXED: Check if color with this name already exists
                        $existingColor = Color::where('name', $colorData['name'])->first();

                        if ($existingColor) {
                            // Use existing color
                            $colorMapping[$colorData['id']] = $existingColor->id;
                            Log::info('Using existing color', [
                                'temp_id' => $colorData['id'],
                                'existing_id' => $existingColor->id,
                                'name' => $colorData['name']
                            ]);
                        } else {
                            // Create new color only if it doesn't exist
                            try {
                                $color = Color::create([
                                    'name' => $colorData['name'],
                                    'hex_code' => $colorData['hex'] ?? $colorData['hex_code'] ?? '#000000',
                                    'is_active' => true,
                                    'sort_order' => 0
                                ]);
                                $colorMapping[$colorData['id']] = $color->id;
                                Log::info('Created new color', [
                                    'temp_id' => $colorData['id'],
                                    'real_id' => $color->id,
                                    'name' => $colorData['name']
                                ]);
                            } catch (\Illuminate\Database\QueryException $e) {
                                // If still fails due to race condition, try to get the existing one
                                if (strpos($e->getMessage(), 'colors_name_unique') !== false) {
                                    $existingColor = Color::where('name', $colorData['name'])->first();
                                    if ($existingColor) {
                                        $colorMapping[$colorData['id']] = $existingColor->id;
                                        Log::info('Race condition resolved - using existing color', [
                                            'temp_id' => $colorData['id'],
                                            'existing_id' => $existingColor->id
                                        ]);
                                    } else {
                                        throw $e; // Re-throw if it's a different issue
                                    }
                                } else {
                                    throw $e; // Re-throw if it's not a duplicate name issue
                                }
                            }
                        }
                    } else {
                        // Use existing color ID
                        $colorMapping[$colorData['id']] = $colorData['id'];
                    }
                }
            }

            // FIXED: Create or get sizes (check for existing ones first)
            $sizeMapping = [];
            foreach ($sizesData as $sizeData) {
                if (is_array($sizeData)) {
                    // Check if it's a temporary ID (new size)
                    if (isset($sizeData['id']) && strpos($sizeData['id'], 'temp_') === 0) {
                        // FIXED: Check if size with this name already exists
                        $existingSize = Size::where('name', $sizeData['name'])
                            ->where('category_type', $sizeData['category_type'] ?? 'general')
                            ->first();

                        if ($existingSize) {
                            // Use existing size
                            $sizeMapping[$sizeData['id']] = $existingSize->id;
                            Log::info('Using existing size', [
                                'temp_id' => $sizeData['id'],
                                'existing_id' => $existingSize->id,
                                'name' => $sizeData['name']
                            ]);
                        } else {
                            // Create new size only if it doesn't exist
                            try {
                                $size = Size::create([
                                    'name' => $sizeData['name'],
                                    'category_type' => $sizeData['category_type'] ?? 'general',
                                    'is_active' => true,
                                    'sort_order' => 0
                                ]);
                                $sizeMapping[$sizeData['id']] = $size->id;
                                Log::info('Created new size', [
                                    'temp_id' => $sizeData['id'],
                                    'real_id' => $size->id,
                                    'name' => $sizeData['name']
                                ]);
                            } catch (\Illuminate\Database\QueryException $e) {
                                // If still fails due to race condition, try to get the existing one
                                if (strpos($e->getMessage(), 'sizes_name_unique') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                    $existingSize = Size::where('name', $sizeData['name'])
                                        ->where('category_type', $sizeData['category_type'] ?? 'general')
                                        ->first();
                                    if ($existingSize) {
                                        $sizeMapping[$sizeData['id']] = $existingSize->id;
                                        Log::info('Race condition resolved - using existing size', [
                                            'temp_id' => $sizeData['id'],
                                            'existing_id' => $existingSize->id
                                        ]);
                                    } else {
                                        throw $e; // Re-throw if it's a different issue
                                    }
                                } else {
                                    throw $e; // Re-throw if it's not a duplicate name issue
                                }
                            }
                        }
                    } else {
                        // Use existing size ID
                        $sizeMapping[$sizeData['id']] = $sizeData['id'];
                    }
                }
            }

            // Create variants with mapped IDs
            foreach ($variantsData as $variantData) {
                if (is_array($variantData)) {
                    $realColorId = $colorMapping[$variantData['color_id']] ?? null;
                    $realSizeId = $sizeMapping[$variantData['size_id']] ?? null;

                    if ($realColorId && $realSizeId) {
                        // FIXED: Check if variant already exists for this product
                        $existingVariant = ProductVariant::where('product_id', $product->id)
                            ->where('color_id', $realColorId)
                            ->where('size_id', $realSizeId)
                            ->first();

                        if ($existingVariant) {
                            Log::info('Variant already exists, skipping', [
                                'product_id' => $product->id,
                                'color_id' => $realColorId,
                                'size_id' => $realSizeId,
                                'existing_variant_id' => $existingVariant->id
                            ]);
                            continue;
                        }

                        $variant = new ProductVariant([
                            'product_id' => $product->id,
                            'color_id' => $realColorId,
                            'size_id' => $realSizeId,
                            'stock' => $variantData['stock'] ?? 0,
                            'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                            'is_active' => $variantData['is_active'] ?? true,
                        ]);

                        // The SKU will be generated automatically in the model's boot method
                        $variant->save();

                        Log::info('Created variant', [
                            'variant_id' => $variant->id,
                            'color_id' => $realColorId,
                            'size_id' => $realSizeId,
                            'sku' => $variant->sku
                        ]);
                    } else {
                        Log::warning('Could not create variant - missing color or size mapping', [
                            'original_color_id' => $variantData['color_id'],
                            'original_size_id' => $variantData['size_id'],
                            'mapped_color_id' => $realColorId,
                            'mapped_size_id' => $realSizeId
                        ]);
                    }
                }
            }

            Log::info('Variant creation completed successfully');

        } catch (Exception $e) {
            Log::error('Variant creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'product_id' => $product->id ?? 'unknown'
            ]);
            throw new Exception('Failed to create product variants: ' . $e->getMessage());
        }
    }

// Helper method for unique slug generation
    private function generateUniqueSlug($name, $ignoreId = null)
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->when($ignoreId, function($query, $id) {
            return $query->where('id', '!=', $id);
        })->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

// Helper method for unique slug generation

    public function show(Product $product)
    {
        try {
            $product->load(['category', 'variants.color', 'variants.size']);

            $productData = $product->toArray();
            if ($product->image) {
                $productData['image_url'] = asset('storage/' . $product->image);
            }

            return Inertia::render('Admin/Products/Show', [
                'product' => $productData,
            ]);
        } catch (Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Unable to load product details.');
        }
    }

    public function edit(Product $product)
    {
        try {
            $product->load(['category', 'variants.color', 'variants.size']);

            $categories = Category::active()->ordered()->get();

            // FIXED: Use the safer methods without DISTINCT issues
            $availableColors = $product->getAllAvailableColors();
            $availableSizes = $product->getAllAvailableSizes();

            // Get existing variants
            $existingVariants = $product->variants()->with(['color', 'size'])->get();

            $productData = $product->toArray();
            if ($product->image) {
                $productData['image'] = asset('storage/' . $product->image);
                $productData['image_url'] = asset('storage/' . $product->image);
            }
            $productData['available_colors'] = $availableColors;
            $productData['available_sizes'] = $availableSizes;

            return Inertia::render('Admin/Products/Edit', [
                'product' => $productData,
                'categories' => $categories,
                'existingVariants' => $existingVariants,
            ]);
        } catch (\Exception $e) {
            Log::error('Product edit failed: ' . $e->getMessage());
            return redirect()->route('admin.products.index')
                ->with('error', 'Unable to load product for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'is_active' => 'boolean',
                'is_donatable' => 'boolean',
                'variants' => 'nullable|array',
                'colors' => 'nullable|array',
                'sizes' => 'nullable|array',
            ]);

            Log::info('Product update request data:', [
                'all_data' => $request->all(),
                'has_name' => $request->has('name'),
                'name_value' => $request->get('name'),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type')
            ]);

            DB::beginTransaction();

            // Update basic product info
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'stock' => $validated['stock'],
                'category_id' => $validated['category_id'],
                'is_active' => $validated['is_active'] ?? true,
                'is_donatable' => $validated['is_donatable'] ?? false,
            ]);

            // Handle image upload if present
            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }

                $path = $request->file('image')->store('products', 'public');
                $product->update(['image' => $path]);
            }

            // FIXED: Handle variants with proper duplicate checking
            if ($request->has('variants') && is_array($request->variants)) {

                // Step 1: FIXED - Handle colors with duplicate checking
                $colorIdMap = [];
                if ($request->has('colors') && is_array($request->colors)) {
                    foreach ($request->colors as $colorData) {
                        if (isset($colorData['id']) && str_starts_with($colorData['id'], 'temp_')) {
                            $colorName = $colorData['name'];
                            $colorHex = $colorData['hex'] ?? $colorData['hex_code'] ?? '#000000';

                            // FIXED: Check if color already exists by name OR hex
                            $existingColor = Color::where('name', $colorName)
                                ->orWhere('hex_code', $colorHex)
                                ->first();

                            if ($existingColor) {
                                // Use existing color
                                $colorIdMap[$colorData['id']] = $existingColor->id;

                                Log::info('Using existing color:', [
                                    'temp_id' => $colorData['id'],
                                    'existing_id' => $existingColor->id,
                                    'name' => $existingColor->name
                                ]);
                            } else {
                                // Create new color only if it doesn't exist
                                try {
                                    $newColor = Color::create([
                                        'name' => $colorName,
                                        'hex_code' => $colorHex,
                                        'is_active' => $colorData['is_active'] ?? true,
                                        'sort_order' => $colorData['sort_order'] ?? 0,
                                    ]);
                                    $colorIdMap[$colorData['id']] = $newColor->id;

                                    Log::info('Created new color:', [
                                        'temp_id' => $colorData['id'],
                                        'new_id' => $newColor->id,
                                        'name' => $newColor->name
                                    ]);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    // If still duplicate (race condition), find the existing one
                                    if ($e->getCode() == 23000) { // Integrity constraint violation
                                        $existingColor = Color::where('name', $colorName)->first();
                                        if ($existingColor) {
                                            $colorIdMap[$colorData['id']] = $existingColor->id;
                                            Log::info('Used existing color after duplicate error:', [
                                                'temp_id' => $colorData['id'],
                                                'existing_id' => $existingColor->id
                                            ]);
                                        } else {
                                            throw $e; // Re-throw if it's a different error
                                        }
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                }

                // Step 2: FIXED - Handle sizes with duplicate checking
                $sizeIdMap = [];
                if ($request->has('sizes') && is_array($request->sizes)) {
                    foreach ($request->sizes as $sizeData) {
                        if (isset($sizeData['id']) && str_starts_with($sizeData['id'], 'temp_')) {
                            $sizeName = $sizeData['name'];
                            $categoryType = $sizeData['category_type'] ?? 'general';

                            // FIXED: Check if size already exists by name and category
                            $existingSize = Size::where('name', $sizeName)
                                ->where('category_type', $categoryType)
                                ->first();

                            if ($existingSize) {
                                // Use existing size
                                $sizeIdMap[$sizeData['id']] = $existingSize->id;

                                Log::info('Using existing size:', [
                                    'temp_id' => $sizeData['id'],
                                    'existing_id' => $existingSize->id,
                                    'name' => $existingSize->name
                                ]);
                            } else {
                                // Create new size only if it doesn't exist
                                try {
                                    $newSize = Size::create([
                                        'name' => $sizeName,
                                        'category_type' => $categoryType,
                                        'is_active' => $sizeData['is_active'] ?? true,
                                        'sort_order' => $sizeData['sort_order'] ?? 0,
                                    ]);
                                    $sizeIdMap[$sizeData['id']] = $newSize->id;

                                    Log::info('Created new size:', [
                                        'temp_id' => $sizeData['id'],
                                        'new_id' => $newSize->id,
                                        'name' => $newSize->name
                                    ]);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    // If still duplicate (race condition), find the existing one
                                    if ($e->getCode() == 23000) { // Integrity constraint violation
                                        $existingSize = Size::where('name', $sizeName)
                                            ->where('category_type', $categoryType)
                                            ->first();
                                        if ($existingSize) {
                                            $sizeIdMap[$sizeData['id']] = $existingSize->id;
                                            Log::info('Used existing size after duplicate error:', [
                                                'temp_id' => $sizeData['id'],
                                                'existing_id' => $existingSize->id
                                            ]);
                                        } else {
                                            throw $e;
                                        }
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                }

                // Step 3: Update/create variants with mapped IDs
                $variantIds = [];
                foreach ($request->variants as $variantData) {
                    // Map temporary IDs to real IDs
                    $colorId = $variantData['color_id'];
                    $sizeId = $variantData['size_id'];

                    // Replace temp IDs with real IDs
                    if (isset($colorIdMap[$colorId])) {
                        $colorId = $colorIdMap[$colorId];
                    }
                    if (isset($sizeIdMap[$sizeId])) {
                        $sizeId = $sizeIdMap[$sizeId];
                    }

                    // Skip if IDs are still temporary
                    if (is_string($colorId) && str_starts_with($colorId, 'temp_')) {
                        Log::warning('Skipping variant with unmapped color ID:', ['color_id' => $colorId]);
                        continue;
                    }
                    if (is_string($sizeId) && str_starts_with($sizeId, 'temp_')) {
                        Log::warning('Skipping variant with unmapped size ID:', ['size_id' => $sizeId]);
                        continue;
                    }

                    // Convert to integers
                    $colorId = (int) $colorId;
                    $sizeId = (int) $sizeId;

                    // FIXED: Check for duplicate variants
                    $existingVariant = null;
                    if (isset($variantData['id']) && is_numeric($variantData['id'])) {
                        // Update existing variant
                        $existingVariant = ProductVariant::where('id', $variantData['id'])
                            ->where('product_id', $product->id)
                            ->first();
                    }

                    if ($existingVariant) {
                        // Update existing variant
                        $existingVariant->update([
                            'color_id' => $colorId,
                            'size_id' => $sizeId,
                            'stock' => (int) $variantData['stock'],
                            'price_adjustment' => (float) ($variantData['price_adjustment'] ?? 0),
                            'is_active' => $variantData['is_active'] ?? true,
                            'sku' => $variantData['sku'] ?? null,
                        ]);
                        $variantIds[] = $existingVariant->id;

                        Log::info('Updated existing variant:', ['variant_id' => $existingVariant->id]);
                    } else {
                        // Check if variant with same color/size combo already exists
                        $duplicateVariant = ProductVariant::where('product_id', $product->id)
                            ->where('color_id', $colorId)
                            ->where('size_id', $sizeId)
                            ->first();

                        if ($duplicateVariant) {
                            // Update the duplicate instead of creating new
                            $duplicateVariant->update([
                                'stock' => (int) $variantData['stock'],
                                'price_adjustment' => (float) ($variantData['price_adjustment'] ?? 0),
                                'is_active' => $variantData['is_active'] ?? true,
                                'sku' => $variantData['sku'] ?? $duplicateVariant->sku,
                            ]);
                            $variantIds[] = $duplicateVariant->id;

                            Log::info('Updated duplicate variant:', ['variant_id' => $duplicateVariant->id]);
                        } else {
                            // Create new variant
                            $variant = ProductVariant::create([
                                'product_id' => $product->id,
                                'color_id' => $colorId,
                                'size_id' => $sizeId,
                                'stock' => (int) $variantData['stock'],
                                'price_adjustment' => (float) ($variantData['price_adjustment'] ?? 0),
                                'is_active' => $variantData['is_active'] ?? true,
                                'sku' => $this->generateSku($product->name, $colorId, $sizeId, $product->id),
                            ]);
                            $variantIds[] = $variant->id;

                            Log::info('Created new variant:', ['variant_id' => $variant->id]);
                        }
                    }
                }

                // Step 4: Delete removed variants
                $product->variants()->whereNotIn('id', $variantIds)->delete();

                Log::info('Variants update completed:', [
                    'kept_variant_ids' => $variantIds,
                    'color_mappings' => $colorIdMap,
                    'size_mappings' => $sizeIdMap,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'Product updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Product update failed: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'request_data' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update product. Please try again.']);
        }
    }

// Helper method for SKU generation
    private function generateSku($productName, $colorId, $sizeId, $productId)
    {
        $color = Color::find($colorId);
        $size = Size::find($sizeId);

        $productCode = strtoupper(substr($productName, 0, 3));
        $colorCode = $color ? strtoupper(substr($color->name, 0, 3)) : 'COL';
        $sizeCode = $size ? strtoupper(substr($size->name, 0, 3)) : 'SIZ';

        return "{$productCode}-{$colorCode}-{$sizeCode}-{$productId}";
    }


    public function destroy(Product $product)
    {
        try {
            DB::transaction(function () use ($product) {
                // Check if product has orders
                if ($product->orderItems()->exists()) {
                    throw new Exception('Cannot delete product that has orders');
                }

                // Delete variants first
                $product->variants()->delete();

                // Delete image
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }

                // Delete product
                $product->delete();
            });

            return redirect()->route('admin.products.index')
                ->with('success', 'Product deleted successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Cannot delete product. It may have existing orders.');
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id'
            ]);

            $deletedCount = 0;
            $skippedCount = 0;

            DB::transaction(function () use ($request, &$deletedCount, &$skippedCount) {
                $products = Product::whereIn('id', $request->product_ids)->get();

                foreach ($products as $product) {
                    if ($product->orderItems()->exists()) {
                        $skippedCount++;
                        continue;
                    }

                    // Delete variants
                    $product->variants()->delete();

                    // Delete image
                    if ($product->image) {
                        Storage::disk('public')->delete($product->image);
                    }

                    $product->delete();
                    $deletedCount++;
                }
            });

            $message = "Deleted {$deletedCount} products.";
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} products that have orders.";
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            return back()->with('error', 'Bulk delete failed. Please try again.');
        }
    }

    // Helper methods remain the same...

    // HELPER: Update product variants safely
    private function updateProductVariants(Product $product, array $variants)
    {
        try {
            foreach ($variants as $variantData) {
                if (isset($variantData['id']) && is_numeric($variantData['id'])) {
                    // Update existing variant
                    $variant = ProductVariant::where('id', $variantData['id'])
                        ->where('product_id', $product->id)
                        ->first();
                    if ($variant) {
                        $variant->update([
                            'stock' => $variantData['stock'] ?? 0,
                            'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                            'is_active' => $variantData['is_active'] ?? true,
                        ]);
                    }
                } else {
                    // Create new variant (similar to createProductVariants)
                    // This handles variants created by adding new colors/sizes
                    if (isset($variantData['color_id']) && isset($variantData['size_id'])) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'color_id' => $variantData['color_id'],
                            'size_id' => $variantData['size_id'],
                            'stock' => $variantData['stock'] ?? 0,
                            'price_adjustment' => $variantData['price_adjustment'] ?? 0,
                            'is_active' => $variantData['is_active'] ?? true,
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Variant update failed: ' . $e->getMessage());
            throw new Exception('Failed to update product variants');
        }
    }
}
