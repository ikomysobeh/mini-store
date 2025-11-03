<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hex_code',
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
        return $query->where('colors.is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('colors.sort_order')->orderBy('colors.name');
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return $this->name;
    }
}
