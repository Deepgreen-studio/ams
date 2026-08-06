<?php

namespace App\Domains\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationAnalyticsHeatmap extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'application_id',
        'metric_date',
        'day_of_week',
        'hour',
        'activity_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicationAnalyticsHeatmap $row): void {
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
            'day_of_week' => 'integer',
            'hour' => 'integer',
            'activity_count' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
