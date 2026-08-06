<?php

namespace App\Domains\Customers\Models;

use App\Domains\Customers\Enums\LicenseStatus;
use App\Models\User;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'subscription_id',
        'customer_id',
        'customer_application_id',
        'license_key',
        'status',
        'starts_at',
        'expires_at',
        'features',
        'max_activations',
        'activation_count',
        'last_validated_at',
        'revoked_at',
        'revoked_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (License $license): void {
            if (blank($license->uuid)) {
                $license->uuid = (string) Str::uuid();
            }

            if (blank($license->license_key)) {
                $license->license_key = self::generateLicenseKey();
            }
        });
    }

    protected static function newFactory(): LicenseFactory
    {
        return LicenseFactory::new();
    }

    public static function generateLicenseKey(): string
    {
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(Str::random(4));
        }

        return implode('-', $segments);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'features' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_validated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerApplication(): BelongsTo
    {
        return $this->belongsTo(CustomerApplication::class);
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
