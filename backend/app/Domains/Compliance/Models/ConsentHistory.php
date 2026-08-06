<?php

namespace App\Domains\Compliance\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\ConsentHistoryAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConsentHistory extends Model
{
    public $timestamps = false;

    protected $table = 'consent_history';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_consent_id',
        'consent_type_id',
        'company_id',
        'action',
        'from_status',
        'to_status',
        'from_version',
        'to_version',
        'from_granted',
        'to_granted',
        'ip_address',
        'device',
        'source',
        'acted_by',
        'comments',
        'metadata',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ConsentHistory $history): void {
            if (blank($history->uuid)) {
                $history->uuid = (string) Str::uuid();
            }
            if (blank($history->created_at)) {
                $history->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ConsentHistoryAction::class,
            'from_granted' => 'boolean',
            'to_granted' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function userConsent(): BelongsTo
    {
        return $this->belongsTo(UserConsent::class);
    }

    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
