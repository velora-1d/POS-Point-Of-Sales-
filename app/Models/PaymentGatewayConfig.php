<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentGatewayConfig extends Model
{
    use HasFactory;

    protected $table = 'payment_gateway_configs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'provider',
        'is_active',
        'base_url',
        'project_slug',
        'callback_url',
        'api_key_encrypted',
        'api_secret_encrypted',
        'active_payment_methods',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_payment_methods' => 'array',
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
