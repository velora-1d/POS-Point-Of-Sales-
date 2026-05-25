<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftTemplate extends Model
{
    use HasFactory;

    protected $table = 'shift_templates';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Hanya memiliki created_at, ditangani DB default

    protected $fillable = [
        'id',
        'outlet_id',
        'name',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }
}
