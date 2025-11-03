<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'product_name',
        'variant_id',
        'selected_color_id',
        'selected_size_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($cartItem) {
            $cartItem->cart->updateTotal();
        });

        static::deleted(function ($cartItem) {
            $cartItem->cart->updateTotal();
        });
    }
    // Add these new relationships
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function selectedColor()
    {
        return $this->belongsTo(Color::class, 'selected_color_id');
    }

    public function selectedSize()
    {
        return $this->belongsTo(Size::class, 'selected_size_id');
    }

    // Add this method
    public function getEffectivePriceAttribute()
    {
        if ($this->variant) {
            return $this->variant->final_price;
        }

        return $this->price;
    }
}
