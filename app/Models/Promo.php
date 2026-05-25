<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $table = 'promos';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'name',
        'code',
        'type',
        'apply_method',
        'discount_percent',
        'discount_amount',
        'max_discount_amount',
        'min_transaction_amount',
        'buy_quantity',
        'get_quantity',
        'can_stack',
        'usage_limit',
        'usage_count',
        'start_date',
        'end_date',
        'happy_hour_start',
        'happy_hour_end',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_transaction_amount' => 'decimal:2',
            'buy_quantity' => 'integer',
            'get_quantity' => 'integer',
            'can_stack' => 'boolean',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function rules()
    {
        return $this->hasMany(PromoRule::class, 'promo_id');
    }
}
