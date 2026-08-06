<?php

namespace App\Domains\Audit\Models;

use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'company_id',
        'module',
        'action',
        'subject_type',
        'subject_id',
        'before_data',
        'after_data',
        'changed_fields',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log): void {
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
            'before_data' => 'array',
            'after_data' => 'array',
            'changed_fields' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
