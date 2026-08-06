<?php

namespace App\Domains\Support\Models;

use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportSlaHoliday extends Model
{
    protected $table = 'support_sla_holidays';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'support_sla_calendar_id',
        'name',
        'holiday_date',
        'is_recurring',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupportSlaHoliday $holiday): void {
            if (blank($holiday->uuid)) {
                $holiday->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(SupportSlaCalendar::class, 'support_sla_calendar_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
