<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\DataMapping;
use App\Domains\Integrations\Models\DataMappingField;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class DataMappingFieldRepository extends BaseRepository
{
    public function __construct(DataMappingField $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return Collection<int, DataMappingField>
     */
    public function syncForMapping(DataMapping $mapping, array $fields): Collection
    {
        $mapping->fields()->delete();

        $created = collect();
        foreach (array_values($fields) as $index => $field) {
            $created->push($mapping->fields()->create([
                'external_field' => (string) ($field['external_field'] ?? ''),
                'internal_field' => (string) ($field['internal_field'] ?? ''),
                'transform_type' => (string) ($field['transform_type'] ?? 'none'),
                'transform_config' => $field['transform_config'] ?? null,
                'is_required' => (bool) ($field['is_required'] ?? false),
                'default_value' => array_key_exists('default_value', $field)
                    ? (is_scalar($field['default_value']) || $field['default_value'] === null
                        ? $field['default_value']
                        : json_encode($field['default_value']))
                    : null,
                'custom_rules' => $field['custom_rules'] ?? null,
                'sort_order' => (int) ($field['sort_order'] ?? $index),
                'is_enabled' => array_key_exists('is_enabled', $field) ? (bool) $field['is_enabled'] : true,
                'notes' => $field['notes'] ?? null,
            ]));
        }

        return $created;
    }
}
