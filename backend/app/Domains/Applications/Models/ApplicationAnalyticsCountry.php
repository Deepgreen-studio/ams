<?php

namespace App\Domains\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationAnalyticsCountry extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'metric_date',
        'country_code',
        'country_name',
        'users',
        'sessions',
        'installs',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationAnalyticsCountry $row): void {
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
            'installs' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
