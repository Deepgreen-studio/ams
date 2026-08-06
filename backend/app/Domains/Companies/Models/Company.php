<?php

namespace App\Domains\Companies\Models;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'company_name',
        'legal_name',
        'registration_number',
        'tax_number',
        'email',
        'phone',
        'website',
        'logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'language',
        'currency',
        'date_format',
        'time_format',
        'business_hours',
        'settings',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'logo_url',
        'favicon_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (blank($company->uuid)) {
                $company->uuid = (string) Str::uuid();
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
            'settings' => 'array',
            'status' => CompanyStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'company_name',
                'legal_name',
                'email',
                'phone',
                'status',
                'logo',
                'favicon',
                'primary_color',
                'secondary_color',
                'timezone',
                'language',
                'currency',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->logo);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->favicon);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(CompanyLocation::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_primary', 'status'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk(config('filesystems.company_media_disk', 'public'))->url($path);
    }
}
