<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TableQrConfig extends Model
{
    use HasFactory;

    protected $table = 'table_qr_configs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'store_slug',
        'qr_template',
        'primary_color',
        'bulk_regenerated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'bulk_regenerated_at' => 'datetime',
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
