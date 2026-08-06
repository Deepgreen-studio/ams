<?php

namespace App\Domains\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationAnalyticsDevice extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'metric_date',
        'device_model',
        'os_name',
        'os_version',
        'users',
        'sessions',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationAnalyticsDevice $row): void {
            if (blank($row->uuid)) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'users' => 'integer',
            'sessions' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
