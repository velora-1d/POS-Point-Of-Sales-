<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipTier extends Model
{
    use HasFactory;

    protected $table = 'membership_tiers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'tier',
        'name',
        'point_threshold',
        'point_rate_per_amount',
        'discount_percent',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'point_threshold' => 'integer',
            'point_rate_per_amount' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class, 'tier_id');
    }
}
