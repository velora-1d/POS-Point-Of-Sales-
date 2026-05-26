<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RawMaterial extends Model
{
    use HasFactory;

    protected $table = 'raw_materials';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'name',
        'unit',
        'quantity',
        'minimum_stock',
        'cost_per_unit',
        'track_expired',
        'expired_action',
        'expired_reminder_days',
        'is_active',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'cost_per_unit' => 'decimal:2',
            'track_expired' => 'boolean',
            'expired_reminder_days' => 'array',
            'is_active' => 'boolean',
            'last_restocked_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rawMaterial) {
            if (empty($rawMaterial->id)) {
                $rawMaterial->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class, 'raw_material_id');
    }
}
