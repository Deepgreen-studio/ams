<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Compliance\Enums\PrivacyRequestLogAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PrivacyRequestLog extends Model
{
    public $timestamps = false;

    protected $table = 'privacy_request_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'privacy_request_id',
        'from_status',
        'to_status',
        'action',
        'acted_by',
        'comments',
        'metadata',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrivacyRequestLog $log): void {
            if (blank($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            if (blank($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => PrivacyRequestLogAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function privacyRequest(): BelongsTo
    {
        return $this->belongsTo(PrivacyRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
