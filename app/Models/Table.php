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
        'current_guests',
        'occupied_at',
        'qr_code',
        'barcode_tracking',
        'qr_session_token',
        'position_x',
        'position_y',
        'status',
        'is_active',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'current_guests' => 'integer',
            'position_x' => 'integer',
            'position_y' => 'integer',
            'is_active' => 'boolean',
            'occupied_at' => 'datetime',
        ];
    }

    public function getRemainingCapacityAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - ($this->current_guests ?? 0));
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

    public function reservations()
    {
        return $this->hasMany(TableReservation::class, 'table_id');
    }

    public function activeReservation()
    {
        return $this->hasOne(TableReservation::class, 'table_id')
            ->where('status', 'booked')
            ->orderBy('reserved_for');
    }
}
