<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationBeneficiary extends Model
{
    // SIMPLIFIED: Removed unwanted fillable fields
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        // REMOVED: 'email',
        'organization_name',
        // REMOVED: 'relationship_to_donor',
        'special_instructions',
        'is_organization',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class, 'beneficiary_id');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if ($this->is_organization) {
            return $this->organization_name;
        }
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getDisplayNameAttribute()
    {
        return $this->is_organization
            ? $this->organization_name
            : $this->full_name;
    }
}
