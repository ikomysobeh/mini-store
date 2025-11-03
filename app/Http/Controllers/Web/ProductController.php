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
    public function index(Request $request)
    {
        $query = Product::active()->with('category');

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

        $products->getCollection()->transform(function ($product) {
            $productArray = $product->toArray();
            if ($product->image) {
                $productArray['image'] = asset('storage/' . $product->image);
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
            'settings' => $settings, // Add this
            'filters' => $request->only(['category', 'search', 'donatable']),
        ]);
    }

    public function show(Product $product)
    {
        $product->load([
            'category',
            'variants.color',
            'variants.size'
        ]);

        $productData = $product->toArray();
        if ($product->image) {
            $productData['image_url'] = asset('storage/' . $product->image);
            $productData['image'] = asset('storage/' . $product->image);
        }

        // ADD: Check if product has variants
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

        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get()
            ->map(function ($product) {
                $productArray = $product->toArray();
                if ($product->image) {
                    $productArray['image'] = asset('storage/' . $product->image);
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
    }}
