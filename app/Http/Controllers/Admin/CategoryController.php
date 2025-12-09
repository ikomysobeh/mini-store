<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{


    public function index(Request $request)
    {
        $query = Category::withCount('products');

        // Search filter
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name_en', 'like', '%' . $request->search . '%')
                    ->orWhere('name_ar', 'like', '%' . $request->search . '%')
                    ->orWhere('description_en', 'like', '%' . $request->search . '%')
                    ->orWhere('description_ar', 'like', '%' . $request->search . '%')
                    ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        // Other potential filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->ordered()->get();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }
    public function create()
    {
        return Inertia::render('Admin/Categories/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'is_active' => 'required|boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        Category::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'slug' => Str::slug($request->name_en),
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'is_active' => $request->is_active,
            'sort_order' => $request->sort_order,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully');
    }

    public function show(Category $category)
    {
        $category->load(['products' => function($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('Admin/Categories/Show', [
            'category' => $category,
        ]);
    }

    public function edit(Category $category)
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'is_active' => 'required|boolean',
            'sort_order' => 'required|integer|min:0',
        ]);

        $category->update([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'slug' => Str::slug($request->name_en),
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'is_active' => $request->is_active,
            'sort_order' => $request->sort_order,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->withErrors(['category' => 'Cannot delete category with products']);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully');
    }
}
