<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApprovalRule extends Model
{
    use HasFactory;

    protected $table = 'approval_rules';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'manual_discount_enabled',
        'manual_discount_threshold',
        'order_edit_enabled',
        'order_edit_threshold',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'manual_discount_enabled' => 'boolean',
            'manual_discount_threshold' => 'float',
            'order_edit_enabled' => 'boolean',
            'order_edit_threshold' => 'float',
            'metadata' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rule) {
            if (empty($rule->id)) {
                $rule->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }
}
