<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->id)) {
                $template->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'shift_template_id');
    }
}
