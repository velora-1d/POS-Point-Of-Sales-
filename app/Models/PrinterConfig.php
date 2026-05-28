<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrinterConfig extends Model
{
    use HasFactory;

    protected $table = 'printer_configs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'printer_type',
        'connection_type',
        'device_name',
        'ip_address',
        'port',
        'default_receipt_method',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($config) {
            if (empty($config->id)) {
                $config->id = (string) Str::uuid();
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }
}
