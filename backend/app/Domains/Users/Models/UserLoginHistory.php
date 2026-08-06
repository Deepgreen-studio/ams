<?php

namespace App\Domains\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Login history for authentication auditing.
 */
class UserLoginHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'ip_address',
        'user_agent',
        'device',
        'platform',
        'operating_system',
        'browser',
        'location',
        'country',
        'city',
        'status',
        'session_id',
        'logged_in_at',
        'logout_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserLoginHistory $history): void {
            if (blank($history->uuid)) {
                $history->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
            'logout_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
