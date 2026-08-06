<?php

namespace App\Domains\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'endpoint',
        'method',
        'request',
        'response',
        'response_code',
        'duration',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApiLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
            'response_code' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
