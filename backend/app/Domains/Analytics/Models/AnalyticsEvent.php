<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Database\Factories\AnalyticsEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AnalyticsEvent extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'application_id',
        'customer_id',
        'category',
        'event_name',
        'event_source',
        'subject_type',
        'subject_id',
        'properties',
        'metrics',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyticsEvent $event): void {
            if (blank($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }

            if (blank($event->occurred_at)) {
                $event->occurred_at = now();
            }
        });
    }

    protected static function newFactory(): AnalyticsEventFactory
    {
        return AnalyticsEventFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AnalyticsCategory::class,
            'properties' => 'array',
            'metrics' => 'array',
            'occurred_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
