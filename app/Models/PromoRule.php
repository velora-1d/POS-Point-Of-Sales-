<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromoRule extends Model
{
    use HasFactory;

    protected $table = 'promo_rules';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'promo_id',
        'trigger',
        'reference_id',
        'reference_value',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rule) {
            if (empty($rule->id)) {
                $rule->id = (string) Str::uuid();
            }
        });
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
