<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $fillable = [
        'app_user_id', 'username', 'ip', 'machine_code', 'success', 'reason',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
