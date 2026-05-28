<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'outlet_id',
        'shift_id',
        'table_id',
        'customer_id',
        'cashier_id',
        'order_number',
        'source',
        'type',
        'status',
        'subtotal',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'notes',
        'estimated_time',
        'cooking_started_at',
        'pending_started_at',
        'receipt_method',
        'receipt_phone',
        'is_printed',
        'external_order_id',
        'external_platform',
        'pay_later',
        'qr_session_token',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_printed' => 'boolean',
            'pay_later' => 'boolean',
            'metadata' => 'array',
            'cooking_started_at' => 'datetime',
            'pending_started_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->id)) {
                $order->id = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $dateStr = now()->format('Ymd');
                $count = static::whereDate('created_at', now()->toDateString())->count() + 1;
                $order->order_number = 'ORD-' . $dateStr . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class, 'order_id');
    }

    public function paymentLogs()
    {
        return $this->hasMany(OrderPaymentLog::class, 'order_id')
            ->latest('created_at');
    }
}
