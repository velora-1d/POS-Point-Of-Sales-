<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Memiliki created_at tapi tidak updated_at di schema, ditangani DB default

    protected $fillable = [
        'id',
        'product_id',
        'name',
        'additional_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
