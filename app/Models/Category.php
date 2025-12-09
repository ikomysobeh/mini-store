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
        'slug',
        'name_ar',
        'name_en',
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

    /**
     * The accessors to append to the model's array form.
     * This ensures 'name' and 'description' are included in JSON responses.
     */
    protected $appends = [
        'name',
        'description',
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
            // Auto-generate slug from English name
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name_en ?? 'category');
            }
        });
        
        static::updating(function ($category) {
            // Auto-update slug if name_en changed
            if ($category->isDirty('name_en') && !empty($category->name_en)) {
                $category->slug = Str::slug($category->name_en);
            }
        });
    }
}
