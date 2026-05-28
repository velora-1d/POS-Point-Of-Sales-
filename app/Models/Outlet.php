<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    use HasFactory;

    protected $table = 'outlets';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'address',
        'phone',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'outlet_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'outlet_id');
    }

    public function tables()
    {
        return $this->hasMany(Table::class, 'outlet_id');
    }

    public function paymentGatewayConfig()
    {
        return $this->hasOne(PaymentGatewayConfig::class, 'outlet_id');
    }

    public function printerConfig()
    {
        return $this->hasOne(PrinterConfig::class, 'outlet_id');
    }

    public function tableQrConfig()
    {
        return $this->hasOne(TableQrConfig::class, 'outlet_id');
    }

    public function notificationSetting()
    {
        return $this->hasOne(NotificationSetting::class, 'outlet_id');
    }

    public function backupSecuritySetting()
    {
        return $this->hasOne(BackupSecuritySetting::class, 'outlet_id');
    }

    public function approvalRule()
    {
        return $this->hasOne(ApprovalRule::class, 'outlet_id');
    }

    public function onlineOrderIntegrations()
    {
        return $this->hasMany(OnlineOrderIntegration::class, 'outlet_id');
    }
}
