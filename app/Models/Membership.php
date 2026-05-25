<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    protected $table = 'memberships';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Memiliki joined_at & updated_at tapi tidak created_at, ditangani DB default

    protected $fillable = [
        'id',
        'customer_id',
        'tier_id',
        'total_points',
        'lifetime_points',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'integer',
            'lifetime_points' => 'integer',
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function tier()
    {
        return $this->belongsTo(MembershipTier::class, 'tier_id');
    }
}
