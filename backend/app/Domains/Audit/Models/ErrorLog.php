<?php

namespace App\Domains\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ErrorLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'exception',
        'message',
        'file',
        'line',
        'stack_trace',
        'url',
        'method',
        'user_id',
        'ip_address',
        'context',
    ];

    protected static function booted(): void
    {
        static::creating(function (ErrorLog $log): void {
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
            'line' => 'integer',
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
