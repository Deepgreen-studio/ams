<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\DataMappingDirection;
use App\Domains\Integrations\Enums\DataMappingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DataMapping extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'integration_id', 'name', 'slug', 'description',
        'direction', 'status', 'source_entity', 'target_entity', 'version',
        'is_active', 'external_schema', 'sample_payload', 'options',
        'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (DataMapping $mapping): void {
            if (blank($mapping->uuid)) {
                $mapping->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'direction' => DataMappingDirection::class,
            'status' => DataMappingStatus::class,
            'version' => 'integer',
            'is_active' => 'boolean',
            'external_schema' => 'array',
            'sample_payload' => 'array',
            'options' => 'array',
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

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DataMappingField::class)->orderBy('sort_order');
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
