<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * ENHANCED: Display product listing with multiple images support
     */
    public function index(Request $request)
    {
        // ENHANCED: Load images relationship
        $query = Product::active()->with(['category', 'images' => function($query) {
            $query->orderBy('sort_order', 'asc');
        }]);

        if ($request->category) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->donatable) {
            $query->donatable();
        }

        $products = $query->paginate(12);
        $categories = Category::active()->ordered()->get();

        // ENHANCED: Transform products with multiple images support
        $products->getCollection()->transform(function ($product) {
            $productArray = $product->toArray();

            // ENHANCED: Add multiple images support
            if ($product->images->count() > 0) {
                // Use primary image or first image
                $primaryImage = $product->images->where('is_primary', true)->first();
                $firstImage = $primaryImage ?? $product->images->first();

                $productArray['image'] = $firstImage->image_url;
                $productArray['primary_image'] = $firstImage->image_url;

                // Add all images for potential hover effects or galleries
                $productArray['all_images'] = $product->images->map(function($image) {
                    return $image->image_url;
                })->toArray();

                $productArray['images_count'] = $product->images->count();
            } else {
                // Fallback to legacy single image
                if ($product->image) {
                    $productArray['image'] = asset('storage/' . $product->image);
                    $productArray['primary_image'] = asset('storage/' . $product->image);
                    $productArray['all_images'] = [asset('storage/' . $product->image)];
                } else {
                    $productArray['image'] = null;
                    $productArray['primary_image'] = null;
                    $productArray['all_images'] = [];
                }
                $productArray['images_count'] = $product->image ? 1 : 0;
            }

            return $productArray;
        });

        $settings = Setting::public()->get()->pluck('value', 'key');
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        return Inertia::render('Web/Products', [
            'products' => $products,
            'categories' => $categories,
            'settings' => $settings,
            'filters' => $request->only(['category', 'search', 'donatable']),
        ]);
    }

    /**
     * ENHANCED: Display single product with full image gallery
     */
    public function show(Product $product)
    {
        // ENHANCED: Load all relationships including ordered images
        $product->load([
            'category',
            'variants.color',
            'variants.size',
            'images' => function($query) {
                $query->orderBy('sort_order', 'asc');
            }
        ]);

        $productData = $product->toArray();

        // ENHANCED: Multiple images support for product detail
        if ($product->images->count() > 0) {
            // Get primary image
            $primaryImage = $product->images->where('is_primary', true)->first();
            $firstImage = $primaryImage ?? $product->images->first();

            // Set main image (for backward compatibility)
            $productData['image_url'] = $firstImage->image_url;
            $productData['image'] = $firstImage->image_url;
            $productData['primary_image'] = $firstImage->image_url;

            // Add complete image gallery
            $productData['gallery'] = $product->images->map(function($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->image_url,
                    'alt' => $image->alt_text ?? '',
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                ];
            })->toArray();

            // All image URLs for simple access
            $productData['all_images'] = $product->images->pluck('image_url')->toArray();
            $productData['images_count'] = $product->images->count();
            $productData['has_gallery'] = $product->images->count() > 1;

        } else {
            // Fallback to legacy single image
            if ($product->image) {
                $productData['image_url'] = asset('storage/' . $product->image);
                $productData['image'] = asset('storage/' . $product->image);
                $productData['primary_image'] = asset('storage/' . $product->image);
                $productData['gallery'] = [[
                    'id' => 0,
                    'url' => asset('storage/' . $product->image),
                    'alt' => $product->name,
                    'is_primary' => true,
                    'sort_order' => 1,
                ]];
                $productData['all_images'] = [asset('storage/' . $product->image)];
                $productData['images_count'] = 1;
                $productData['has_gallery'] = false;
            } else {
                $productData['image_url'] = null;
                $productData['image'] = null;
                $productData['primary_image'] = null;
                $productData['gallery'] = [];
                $productData['all_images'] = [];
                $productData['images_count'] = 0;
                $productData['has_gallery'] = false;
            }
        }

        // Check if product has variants (existing code)
        $productData['has_variants'] = $product->variants()->exists();

        if ($productData['has_variants']) {
            // Get available colors and sizes
            $productData['available_colors'] = $product->variants()
                ->with('color')
                ->get()
                ->pluck('color')
                ->unique('id')
                ->values();

            $productData['available_sizes'] = $product->variants()
                ->with('size')
                ->get()
                ->pluck('size')
                ->unique('id')
                ->values();

            // Get all variants for selection
            $productData['variants'] = $product->variants->map(function($variant) {
                return [
                    'id' => $variant->id,
                    'color_id' => $variant->color_id,
                    'size_id' => $variant->size_id,
                    'stock' => $variant->stock,
                    'price' => $variant->final_price,
                    'sku' => $variant->sku,
                ];
            });
        }

        // ENHANCED: Related products with multiple images support
        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images' => function($query) {
                $query->orderBy('sort_order', 'asc');
            }])
            ->take(4)
            ->get()
            ->map(function ($product) {
                $productArray = $product->toArray();

                // ENHANCED: Use best available image
                if ($product->images->count() > 0) {
                    $primaryImage = $product->images->where('is_primary', true)->first();
                    $firstImage = $primaryImage ?? $product->images->first();
                    $productArray['image'] = $firstImage->image_url;
                    $productArray['images_count'] = $product->images->count();
                } else {
                    // Fallback to legacy image
                    if ($product->image) {
                        $productArray['image'] = asset('storage/' . $product->image);
                    }
                    $productArray['images_count'] = $product->image ? 1 : 0;
                }

                return $productArray;
            });

        $settings = Setting::public()->get()->pluck('value', 'key');
        if (isset($settings['logo']) && $settings['logo']) {
            $settings['logo_url'] = asset('storage/' . $settings['logo']);
        }

        return Inertia::render('Web/ProductDetail', [
            'product' => $productData,
            'settings' => $settings,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
