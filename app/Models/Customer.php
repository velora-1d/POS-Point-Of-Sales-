<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'name',
        'phone',
        'email',
        'birthdate',
        'is_active',
        'registered_via',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'birthdate' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->id)) {
                $customer->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function membership()
    {
        return $this->hasOne(Membership::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class, 'customer_id')
            ->ofMany('created_at', 'max');
    }

    public function recentOrders()
    {
        return $this->hasMany(Order::class, 'customer_id')
            ->latest()
            ->limit(5);
    }

    public function kasbonOrders()
    {
        return $this->hasMany(Order::class, 'customer_id')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->where('status', '!=', 'cancelled')
            ->latest();
    }

    public function latestKasbonOrder()
    {
        return $this->hasOne(Order::class, 'customer_id')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->where('status', '!=', 'cancelled')
            ->ofMany('created_at', 'max');
    }

    public function reservations()
    {
        return $this->hasMany(TableReservation::class, 'customer_id');
    }
}
