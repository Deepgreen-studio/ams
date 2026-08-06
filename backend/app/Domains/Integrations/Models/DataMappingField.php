<?php

namespace App\Domains\Integrations\Models;

use App\Domains\Integrations\Enums\MappingTransformType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DataMappingField extends Model
{
    protected $fillable = [
        'uuid', 'data_mapping_id', 'external_field', 'internal_field',
        'transform_type', 'transform_config', 'is_required', 'default_value',
        'custom_rules', 'sort_order', 'is_enabled', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (DataMappingField $field): void {
            if (blank($field->uuid)) {
                $field->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'transform_type' => MappingTransformType::class,
            'transform_config' => 'array',
            'is_required' => 'boolean',
            'custom_rules' => 'array',
            'sort_order' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(DataMapping::class, 'data_mapping_id');
    }
}
