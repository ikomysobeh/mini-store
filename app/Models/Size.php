<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variants');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('sizes.is_active', true);
    }

    public function scopeForCategory($query, $category)
    {
        return $query->where('sizes.category_type', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sizes.sort_order')->orderBy('sizes.name');
    }

    // Static methods
    public static function getCategoryTypes()
    {
        return [
            'general' => 'General',
            'clothing' => 'Clothing',
            'shoes' => 'Shoes',
            'accessories' => 'Accessories',
        ];
    }
}
