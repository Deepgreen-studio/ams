<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\PaymentProvider;
use App\Domains\Customers\Enums\PaymentStatus;
use App\Domains\Customers\Enums\SubscriptionPlanType;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Models\User;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'customer_id',
        'customer_application_id',
        'plan_type',
        'plan_name',
        'status',
        'starts_at',
        'expires_at',
        'renews_at',
        'trial_ends_at',
        'cancelled_at',
        'features',
        'payment_status',
        'payment_provider',
        'external_subscription_id',
        'external_customer_id',
        'currency',
        'amount',
        'renewal_reminder_days',
        'last_renewal_reminder_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription): void {
            if (blank($subscription->uuid)) {
                $subscription->uuid = (string) Str::uuid();
            }
        });
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan_type' => SubscriptionPlanType::class,
            'status' => SubscriptionStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_provider' => PaymentProvider::class,
            'features' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'renews_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_renewal_reminder_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isRenewalDueSoon(?Carbon $asOf = null): bool
    {
        if (! $this->renews_at && ! $this->expires_at) {
            return false;
        }

        $asOf ??= now();
        $target = $this->renews_at ?? $this->expires_at;
        $days = (int) ($this->renewal_reminder_days ?: config('billing.renewal_reminder_days', 14));

        return $target->lessThanOrEqualTo($asOf->copy()->addDays($days))
            && $target->greaterThanOrEqualTo($asOf);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerApplication(): BelongsTo
    {
        return $this->belongsTo(CustomerApplication::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
