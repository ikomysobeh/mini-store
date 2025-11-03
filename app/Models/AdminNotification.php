<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Scopes for easy querying
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    // Helper methods
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
    }

    public function isRead()
    {
        return !is_null($this->read_at);
    }

    public function isUnread()
    {
        return is_null($this->read_at);
    }

    // Get formatted time
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // Get notification icon based on type
    public function getIconAttribute()
    {
        return match($this->type) {
            'new_order' => 'shopping-cart',
            'new_donation' => 'heart',
            'low_stock' => 'alert-triangle',
            'order_cancelled' => 'x-circle',
            default => 'bell'
        };
    }

    // Get notification color based on type
    public function getColorAttribute()
    {
        return match($this->type) {
            'new_order' => 'blue',
            'new_donation' => 'green',
            'low_stock' => 'orange',
            'order_cancelled' => 'red',
            default => 'gray'
        };
    }
}
