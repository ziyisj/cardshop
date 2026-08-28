<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'duration_days',
        'is_active', 'sort',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function cardKeys(): HasMany
    {
        return $this->hasMany(CardKey::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** 该商品当前可售的库存（未使用卡密数量） */
    public function stock(): int
    {
        return $this->cardKeys()->where('status', 'unused')->count();
    }
}
