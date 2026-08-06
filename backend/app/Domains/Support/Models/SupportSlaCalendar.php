<?php

namespace App\Domains\Support\Models;

use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportSlaCalendar extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'support_sla_calendars';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'timezone',
        'business_hours',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportSlaCalendar $calendar): void {
            if (blank($calendar->uuid)) {
                $calendar->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'timezone', 'business_hours', 'is_default', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(SupportSlaHoliday::class, 'support_sla_calendar_id');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(SupportSlaPolicy::class, 'support_sla_calendar_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
