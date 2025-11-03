<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'product_name',
        'is_donation_item',
        'variant_id',
        'selected_color',
        'selected_size',
        'selected_color_hex',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'is_donation_item' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    public function scopeDonationItems($query)
    {
        return $query->where('is_donation_item', true);
    }

    public function scopePurchaseItems($query)
    {
        return $query->where('is_donation_item', false);
    }
    // Add these new relationships
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
