<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\TranslatableCategoryTrait;

class Category extends Model
{
    use HasFactory, TranslatableCategoryTrait;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'name_ar',
        'name_en',
        'slug_ar',
        'slug_en',
        'description_en',
        'description_ar',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts()
    {
        return $this->hasMany(Product::class)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            // Auto-generate English slug if name_en is provided
            if (!empty($category->name_en) && empty($category->slug_en)) {
                $category->slug_en = Str::slug($category->name_en);
            }
            
            // Auto-generate Arabic slug if name_ar is provided
            if (!empty($category->name_ar) && empty($category->slug_ar)) {
                $category->slug_ar = Str::slug($category->name_ar);
            }
            
            // Fallback for old slug column
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name ?? $category->name_en ?? 'category');
            }
        });
        
        static::updating(function ($category) {
            // Auto-update English slug if name_en changed
            if ($category->isDirty('name_en') && !empty($category->name_en)) {
                $category->slug_en = Str::slug($category->name_en);
            }
            
            // Auto-update Arabic slug if name_ar changed
            if ($category->isDirty('name_ar') && !empty($category->name_ar)) {
                $category->slug_ar = Str::slug($category->name_ar);
            }
        });
    }
}
