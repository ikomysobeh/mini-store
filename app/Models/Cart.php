<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'session_id',
        'total',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function addItem(Product $product, $quantity = 1)
    {
        $existingItem = $this->items()->where('product_id', $product->id)->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem;
        }

        return $this->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $product->price,
            'product_name' => $product->name,
        ]);
    }

    public function updateTotal()
    {
        $this->total = $this->items()->sum(DB::raw('quantity * price'));
        $this->save();
        return $this;
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->sum('quantity');
    }

    public function isEmpty()
    {
        return $this->items()->count() === 0;
    }

    public function clear()
    {
        $this->items()->delete();
        $this->total = 0;
        $this->save();
        return $this;
    }
}
