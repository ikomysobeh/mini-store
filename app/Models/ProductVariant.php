<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color_id',
        'size_id',
        'sku',
        'stock',
        'price_adjustment',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'integer',
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'variant_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('product_variants.is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('product_variants.stock', '>', 0);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_variants.product_id', $productId);
    }

    // Accessors
    public function getFinalPriceAttribute()
    {
        return $this->product->price + $this->price_adjustment;
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->product->name} - {$this->color->name} / {$this->size->name}";
    }

    // Methods
    public function isInStock($quantity = 1)
    {
        return $this->stock >= $quantity;
    }

    public function decrementStock($quantity)
    {
        if ($this->isInStock($quantity)) {
            $this->decrement('stock', $quantity);
            return true;
        }
        return false;
    }

    // FIXED: Improved SKU generation with uniqueness check
    public function generateUniqueSku()
    {
        try {
            // Load relationships if not loaded
            if (!$this->relationLoaded('product')) {
                $this->load('product');
            }
            if (!$this->relationLoaded('color')) {
                $this->load('color');
            }
            if (!$this->relationLoaded('size')) {
                $this->load('size');
            }

            // Safe property access with fallbacks
            $productName = $this->product?->name ?? 'PROD';
            $colorName = $this->color?->name ?? 'COL';
            $sizeName = $this->size?->name ?? 'SIZE';
            $productId = $this->product?->id ?? $this->product_id ?? rand(1000, 9999);

            // Create base SKU
            $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 3));
            $colorCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $colorName), 0, 3));
            $sizeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $sizeName), 0, 2));

            if (empty($productCode)) $productCode = 'PRD';
            if (empty($colorCode)) $colorCode = 'COL';
            if (empty($sizeCode)) $sizeCode = 'SZ';

            $baseSku = "{$productCode}-{$colorCode}-{$sizeCode}-{$productId}";

            // Check for uniqueness and add suffix if needed
            $counter = 0;
            $finalSku = $baseSku;

            while (static::where('sku', $finalSku)->where('id', '!=', $this->id ?? 0)->exists()) {
                $counter++;
                $finalSku = $baseSku . "-" . str_pad($counter, 2, '0', STR_PAD_LEFT);
            }

            return $finalSku;

        } catch (\Exception $e) {
            Log::error('SKU generation failed: ' . $e->getMessage());
            // Ultimate fallback
            return 'VAR-' . time() . '-' . rand(100, 999);
        }
    }

    // FIXED: Safe boot method with better error handling
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            if (!$variant->sku) {
                try {
                    $variant->sku = $variant->generateUniqueSku();
                } catch (\Exception $e) {
                    Log::error('Variant SKU generation failed: ' . $e->getMessage());
                    // Fallback SKU
                    $variant->sku = 'VAR-' . time() . '-' . rand(100, 999);
                }
            }
        });

        static::updating(function ($variant) {
            // Only regenerate SKU if key fields changed and SKU is empty
            if (empty($variant->sku) && ($variant->isDirty(['product_id', 'color_id', 'size_id']))) {
                try {
                    $variant->sku = $variant->generateUniqueSku();
                } catch (\Exception $e) {
                    Log::error('Variant SKU update failed: ' . $e->getMessage());
                }
            }
        });
    }
}
