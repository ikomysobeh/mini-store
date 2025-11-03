<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image',
        'category_id',
        'is_active',
        'is_donatable',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'is_donatable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Basic relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class)->where('product_variants.is_active', true);
    }

    // FIXED: Colors relationship - include sort_order in SELECT for ORDER BY to work
    public function colors()
    {
        return $this->belongsToMany(Color::class, 'product_variants')
            ->distinct()
            ->select(['colors.id', 'colors.name', 'colors.hex_code', 'colors.sort_order'])
            ->where('product_variants.is_active', 1)
            ->where('colors.is_active', 1)
            ->orderBy('colors.sort_order')
            ->orderBy('colors.name');
    }

    // FIXED: Sizes relationship - include sort_order in SELECT for ORDER BY to work
    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_variants')
            ->distinct()
            ->select(['sizes.id', 'sizes.name', 'sizes.category_type', 'sizes.sort_order'])
            ->where('product_variants.is_active', 1)
            ->where('sizes.is_active', 1)
            ->orderBy('sizes.sort_order')
            ->orderBy('sizes.name');
    }

    // Alternative approach - get all colors/sizes without DISTINCT issues
    public function getAllAvailableColors()
    {
        return Color::whereHas('variants', function($query) {
            $query->where('product_id', $this->id)
                ->where('is_active', 1);
        })
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getAllAvailableSizes()
    {
        return Size::whereHas('variants', function($query) {
            $query->where('product_id', $this->id)
                ->where('is_active', 1);
        })
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('products.is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('products.stock', '>', 0);
    }

    public function scopeDonatable($query)
    {
        return $query->where('products.is_donatable', true);
    }

    // FIXED: Available colors - use the new method
    public function getAvailableColorsAttribute()
    {
        return $this->getAllAvailableColors();
    }

    // FIXED: Available sizes - use the new method
    public function getAvailableSizesAttribute()
    {
        return $this->getAllAvailableSizes();
    }

    // Stock and variant management
    public function getTotalStockAttribute()
    {
        if ($this->hasVariants()) {
            return $this->variants()->where('is_active', true)->sum('stock');
        }
        return $this->stock;
    }

    public function hasVariants()
    {
        return $this->variants()->count() > 0;
    }

    public function isInStock($quantity = 1)
    {
        if ($this->hasVariants()) {
            return $this->getTotalStockAttribute() >= $quantity;
        }
        return $this->stock >= $quantity;
    }

    public function decrementStock($quantity)
    {
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            return true;
        }
        return false;
    }

    public function incrementStock($quantity)
    {
        $this->increment('stock', $quantity);
        return $this;
    }

    // Other attributes
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
