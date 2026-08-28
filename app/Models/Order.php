<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no', 'product_id', 'quantity', 'amount', 'contact',
        'status', 'pay_method', 'trade_no', 'paid_at',
        'delivered_cards', 'buyer_ip',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function generateOrderNo(): string
    {
        return date('YmdHis') . strtoupper(Str::random(6));
    }
}
