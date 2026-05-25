<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
