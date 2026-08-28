<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CardKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'product_id', 'duration_days', 'status',
        'used_by', 'used_at', 'valid_until',
    ];

    protected $casts = [
        'used_at'     => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'used_by');
    }

    /** 生成一个随机卡密串，形如 XXXX-XXXX-XXXX-XXXX */
    public static function generateCode(): string
    {
        $seg = fn () => strtoupper(Str::random(4));
        do {
            $code = implode('-', [$seg(), $seg(), $seg(), $seg()]);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function isUsable(): bool
    {
        if ($this->status !== 'unused' && $this->status !== 'sold') {
            return false;
        }
        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }
        return true;
    }
}
