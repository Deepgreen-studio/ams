<?php

namespace App\Domains\Ai\Models;

use App\Domains\Ai\Enums\AiProviderDriver;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AiProvider extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'ai_providers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'company_id', 'name', 'slug', 'driver', 'status', 'base_url',
        'default_model', 'embedding_model', 'authentication_type', 'credentials',
        'config', 'health_status', 'last_health_check_at', 'timeout_seconds',
        'retry_attempts', 'is_default', 'is_enabled', 'created_by', 'updated_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'credentials',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiProvider $provider): void {
            if (blank($provider->uuid)) {
                $provider->uuid = (string) Str::uuid();
            }
            if (blank($provider->slug)) {
                $provider->slug = Str::slug($provider->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'driver' => AiProviderDriver::class,
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['credentials'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
