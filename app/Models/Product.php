<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\TranslatableProductTrait;

class Product extends Model
{
    use HasFactory, TranslatableProductTrait;

    protected $fillable = [
        'slug',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
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

    /**
     * The accessors to append to the model's array form.
     * This ensures 'name' and 'description' are included in JSON responses.
     */
    protected $appends = [
        'name',
        'description',
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
            ->select(['sizes.id', 'sizes.name_en', 'sizes.name_ar', 'sizes.category_type', 'sizes.sort_order'])
            ->where('product_variants.is_active', 1)
            ->where('sizes.is_active', 1)
            ->orderBy('sizes.sort_order')
            ->orderByRaw("COALESCE(sizes.name_en, sizes.name_ar)");
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
            ->ordered()
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

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        // Always use 'slug' as the route key - we handle locale in resolveRouteBinding
        return 'slug';
    }
    
    /**
     * Retrieve the model for a bound value.
     * This method finds the product by slug.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Find by slug
        $product = $this->where('slug', $value)->first();
        if ($product) return $product;
        
        // Last resort: try to find by ID if the value is numeric
        if (is_numeric($value)) {
            return $this->find($value);
        }
        
        return null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Auto-generate slug from English name
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name_en ?? 'product');
            }
        });
        
        static::updating(function ($product) {
            // Auto-update slug if name_en changed
            if ($product->isDirty('name_en') && !empty($product->name_en)) {
                $product->slug = Str::slug($product->name_en);
            }
        });
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

// Add this method to get primary image
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

// Add this accessor to get the best available image
    public function getBestImageAttribute()
    {
        // Try to get primary image first
        $primaryImage = $this->primaryImage;
        if ($primaryImage) {
            return $primaryImage->image_url;
        }

        // Try to get first image from gallery
        $firstImage = $this->images->first();
        if ($firstImage) {
            return $firstImage->image_url;
        }

        // Fall back to old image field
        if ($this->image) {
            return asset('storage/' . $this->image);
        }

        // Return default image if no image exists
        return asset('images/no-image.png');
    }

// Add this method to get all image URLs
    public function getAllImagesAttribute()
    {
        $images = $this->images;

        if ($images->count() > 0) {
            return $images->pluck('image_url')->toArray();
        }

        // Fall back to old image field
        if ($this->image) {
            return [asset('storage/' . $this->image)];
        }

        return [];
    }

// Add this method to check if product has multiple images
    public function hasMultipleImages()
    {
        return $this->images->count() > 1 || ($this->images->count() >= 1 && $this->image);
    }
}
