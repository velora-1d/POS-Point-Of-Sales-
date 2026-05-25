<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $table = 'tables';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'name',
        'capacity',
        'qr_code',
        'barcode_tracking',
        'qr_session_token',
        'position_x',
        'position_y',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'position_x' => 'integer',
            'position_y' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function activeOrder()
    {
        return $this->hasOne(Order::class, 'table_id')
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function activeOrders()
    {
        return $this->hasMany(Order::class, 'table_id')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest();
    }
}
