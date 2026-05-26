<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShiftCashReport extends Model
{
    use HasFactory;

    protected $table = 'shift_cash_reports';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'shift_id',
        'total_orders',
        'total_revenue',
        'total_cash',
        'total_qris',
        'total_debit',
        'total_ewallet',
        'total_kasbon',
        'total_discount',
        'total_refund',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'total_orders' => 'decimal:0',
            'total_revenue' => 'decimal:2',
            'total_cash' => 'decimal:2',
            'total_qris' => 'decimal:2',
            'total_debit' => 'decimal:2',
            'total_ewallet' => 'decimal:2',
            'total_kasbon' => 'decimal:2',
            'total_discount' => 'decimal:2',
            'total_refund' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->id)) {
                $report->id = (string) Str::uuid();
            }
        });
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
