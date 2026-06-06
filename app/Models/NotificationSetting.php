<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'notification_settings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'low_stock_enabled',
        'low_stock_channels',
        'kasbon_due_enabled',
        'kasbon_due_channels',
        'kasbon_due_threshold_days',
        'online_order_enabled',
        'online_order_channels',
        'table_duration_alert_enabled',
        'table_duration_warning_minutes',
        'table_duration_danger_minutes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_enabled' => 'boolean',
            'low_stock_channels' => 'array',
            'kasbon_due_enabled' => 'boolean',
            'kasbon_due_channels' => 'array',
            'kasbon_due_threshold_days' => 'integer',
            'online_order_enabled' => 'boolean',
            'online_order_channels' => 'array',
            'table_duration_alert_enabled' => 'boolean',
            'table_duration_warning_minutes' => 'integer',
            'table_duration_danger_minutes' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($setting) {
            if (empty($setting->id)) {
                $setting->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }
}
