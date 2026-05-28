<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BackupSecuritySetting extends Model
{
    use HasFactory;

    protected $table = 'backup_security_settings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'auto_backup_enabled',
        'auto_backup_frequency',
        'auto_backup_time',
        'retention_days',
        'backup_channel',
        'encryption_enabled',
        'two_factor_required',
        'last_backup_status',
        'last_backup_at',
        'last_backup_file_name',
        'last_backup_size_bytes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'auto_backup_enabled' => 'boolean',
            'encryption_enabled' => 'boolean',
            'two_factor_required' => 'boolean',
            'last_backup_at' => 'datetime',
            'last_backup_size_bytes' => 'integer',
            'retention_days' => 'integer',
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
