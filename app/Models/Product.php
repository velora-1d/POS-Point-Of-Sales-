<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'category_id',
        'name',
        'description',
        'image_url',
        'base_price',
        'hpp',
        'is_available',
        'is_active',
        'track_stock',
        'track_expired',
        'expired_action',
        'expired_reminder_days',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'hpp' => 'decimal:2',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'track_expired' => 'boolean',
            'expired_reminder_days' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id');
    }

    public function stock()
    {
        return $this->hasOne(ProductStock::class, 'product_id');
    }

    public function ingredients()
    {
        return $this->hasMany(ProductIngredient::class, 'product_id');
    }
}
