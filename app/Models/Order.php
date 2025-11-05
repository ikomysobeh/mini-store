<?php

namespace App\Models;

use App\Services\AdminNotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'beneficiary_id',  // ADD THIS LINE
        'total',
        'shipping',
        'status',
        'payment_method',
        'payment_id',
        'paid_at',
        'ready_at',
        'is_donation',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'shipping' => 'decimal:2',
        'paid_at' => 'datetime',
        'ready_at' => 'datetime',
        'is_donation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDonations($query)
    {
        return $query->where('is_donation', true);
    }

    public function scopePurchases($query)
    {
        return $query->where('is_donation', false);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->sum('quantity');
    }

    public function getSubtotalAttribute()
    {
        return $this->items()->sum(DB::raw('quantity * price'));
    }

    public function isPaid()
    {
        return !is_null($this->paid_at);
    }

    public function markAsPaid()
    {
        $this->paid_at = now();
        $this->save();
        return $this;
    }

    public function updateStatus($status)
    {
        $this->status = $status;

        if ($status === self::STATUS_SHIPPED) {
            $this->ready_at = now();
        }

        $this->save();
        return $this;
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
    protected static function booted()
    {
        // Create admin notification when order is created
        static::created(function ($order) {
            // Load relationships for notification
            $order->load(['customer.user', 'items']);

            // Create admin notification
            $notificationService = new AdminNotificationService();
            $notificationService->createOrderNotification($order);
        });

    }

    // Add this relationship method
    public function beneficiary()
    {
        return $this->belongsTo(DonationBeneficiary::class, 'beneficiary_id');
    }

// Add this scope for orders with beneficiaries
    public function scopeWithBeneficiary($query)
    {
        return $query->whereNotNull('beneficiary_id');
    }

// Add this scope for orders without beneficiaries
    public function scopeWithoutBeneficiary($query)
    {
        return $query->whereNull('beneficiary_id');
    }

// Add this accessor to check if order has beneficiary
    public function getHasBeneficiaryAttribute()
    {
        return !is_null($this->beneficiary_id);
    }
}
