<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryExpiry extends Model
{
    use HasFactory;

    protected $table = 'inventory_expiries';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'trackable_type',
        'trackable_id',
        'quantity',
        'batch_code',
        'expired_at',
        'reminder_days',
        'expired_action',
        'is_resolved',
        'resolved_action',
        'resolved_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expired_at' => 'date',
            'reminder_days' => 'array',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expiry) {
            if (empty($expiry->id)) {
                $expiry->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'trackable_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'trackable_id');
    }
}
