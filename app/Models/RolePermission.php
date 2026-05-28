<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permissions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'role_id',
        'permission_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rolePermission) {
            if (empty($rolePermission->id)) {
                $rolePermission->id = (string) Str::uuid();
            }
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
