<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'module',
        'action',
        'description',
    ];

    protected $appends = ['name'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($permission) {
            if (empty($permission->id)) {
                $permission->id = (string) Str::uuid();
            }
        });
    }

    public function getNameAttribute(): string
    {
        return $this->module . ':' . $this->action;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
