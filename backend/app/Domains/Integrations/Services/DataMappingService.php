<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Integrations\Events\DataMappingCreated;
use App\Domains\Integrations\Events\DataMappingDeleted;
use App\Domains\Integrations\Events\DataMappingUpdated;
use App\Domains\Integrations\Models\DataMapping;
use App\Domains\Integrations\Repositories\DataMappingFieldRepository;
use App\Domains\Integrations\Repositories\DataMappingRepository;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Mapping\DTOs\FieldMappingRuleDto;
use App\Shared\Services\Mapping\MappingEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataMappingService
{
    public function __construct(
        private readonly DataMappingRepository $mappingRepository,
        private readonly DataMappingFieldRepository $fieldRepository,
        private readonly IntegrationRepository $integrationRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly MappingEngine $mappingEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $this->normalizeCompanyFilter($filters);
        $this->normalizeIntegrationFilter($filters);

        return $this->mappingRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): DataMapping
    {
        return $this->mappingRepository->findByIdentifierOrFail($identifier);
    }

    public function show(string $identifier): DataMapping
    {
        return $this->find($identifier)->load([
            'company:id,uuid,company_name',
            'integration:id,uuid,name,slug,status,base_url',
            'fields',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): DataMapping
    {
        return DB::transaction(function () use ($data, $actor): DataMapping {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $integration = $this->integrationRepository->findByIdentifierOrFail((string) $data['integration_id']);
            $fields = array_values((array) ($data['fields'] ?? []));

            $definitionErrors = $this->mappingEngine->validateDefinition($fields);
            if ($definitionErrors !== []) {
                throw new ApiException(implode(' ', $definitionErrors), 422);
            }

            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['integration_id'] = $integration->id;
            $payload['slug'] = $this->uniqueSlug($company->id, $payload['slug'] ?? null, $payload['name']);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['direction'] = $payload['direction'] ?? 'inbound';
            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['is_active'] = $payload['is_active'] ?? true;
            $payload['version'] = 1;

            $mapping = $this->mappingRepository->createMapping($payload);
            $this->fieldRepository->syncForMapping($mapping, $fields);
            $mapping = $this->show($mapping->uuid);

            DataMappingCreated::dispatch($mapping, $actor);

            return $mapping;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): DataMapping
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataMapping {
            $mapping = $this->find($identifier);
            $payload = $this->preparePayload($data, true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('name', $payload) && ! array_key_exists('slug', $data)) {
                // keep slug unless explicitly updated
            }

            if (! empty($payload['slug'])) {
                $payload['slug'] = $this->uniqueSlug($mapping->company_id, $payload['slug'], $mapping->name, $mapping->id);
            }

            if (array_key_exists('fields', $data)) {
                $fields = array_values((array) $data['fields']);
                $definitionErrors = $this->mappingEngine->validateDefinition($fields);
                if ($definitionErrors !== []) {
                    throw new ApiException(implode(' ', $definitionErrors), 422);
                }
                $this->fieldRepository->syncForMapping($mapping, $fields);
                $payload['version'] = ((int) $mapping->version) + 1;
            }

            $this->mappingRepository->updateMapping($mapping, $payload);
            $updated = $this->show($identifier);
            DataMappingUpdated::dispatch($updated, $actor);

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        $mapping = $this->find($identifier);
        DataMappingDeleted::dispatch($mapping, $actor);
        $mapping->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function preview(string $identifier, array $payload = []): array
    {
        $mapping = $this->show($identifier);
        $source = (array) ($payload['source'] ?? $mapping->sample_payload ?? []);
        $direction = (string) ($payload['direction'] ?? $mapping->direction?->value ?? 'inbound');

        if ($direction === 'bidirectional') {
            $direction = 'inbound';
        }

        $rules = $this->rulesFromMapping($mapping);
        $result = $this->mappingEngine->map($source, $rules, $direction);

        return [
            'mapping' => $mapping,
            'source' => $source,
            'direction' => $direction,
            'result' => $result->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validatePayload(string $identifier, array $payload = []): array
    {
        $preview = $this->preview($identifier, $payload);

        return [
            'valid' => (bool) ($preview['result']['valid'] ?? false),
            'errors' => $preview['result']['errors'] ?? [],
            'warnings' => $preview['result']['warnings'] ?? [],
            'output' => $preview['result']['output'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogs(): array
    {
        return [
            'transforms' => $this->mappingEngine->transformCatalog(),
            'internal_fields' => $this->mappingEngine->internalFieldCatalog(),
            'directions' => ['inbound', 'outbound', 'bidirectional'],
            'statuses' => ['draft', 'active', 'inactive', 'archived'],
        ];
    }

    /**
     * Transform an arbitrary source using a stored mapping profile.
     * Intended for Sync and other modules.
     *
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public function transformWithProfile(string $identifier, array $source, ?string $direction = null): array
    {
        $mapping = $this->show($identifier);
        $dir = $direction ?: ($mapping->direction?->value ?? 'inbound');
        if ($dir === 'bidirectional') {
            $dir = 'inbound';
        }

        $result = $this->mappingEngine->map($source, $this->rulesFromMapping($mapping), $dir);
        if (! $result->valid) {
            throw new ApiException(implode(' ', $result->errors), 422);
        }

        return $result->output;
    }

    /**
     * @return list<FieldMappingRuleDto>
     */
    protected function rulesFromMapping(DataMapping $mapping): array
    {
        return $mapping->fields->map(fn ($field) => FieldMappingRuleDto::fromArray([
            'external_field' => $field->external_field,
            'internal_field' => $field->internal_field,
            'transform_type' => $field->transform_type?->value ?? $field->transform_type,
            'transform_config' => $field->transform_config ?? [],
            'is_required' => $field->is_required,
            'default_value' => $field->default_value,
            'custom_rules' => $field->custom_rules ?? [],
            'sort_order' => $field->sort_order,
            'is_enabled' => $field->is_enabled,
        ]))->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $partial = false): array
    {
        $keys = [
            'name', 'slug', 'description', 'direction', 'status', 'source_entity',
            'target_entity', 'is_active', 'external_schema', 'sample_payload', 'options',
        ];

        $payload = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) || (! $partial && array_key_exists($key, $data))) {
                if (array_key_exists($key, $data)) {
                    $payload[$key] = $data[$key];
                }
            }
        }

        if (! $partial) {
            foreach (['name', 'source_entity'] as $required) {
                if (empty($payload[$required]) && empty($data[$required])) {
                    // validation handled by FormRequest
                }
            }
        }

        return $payload;
    }

    protected function uniqueSlug(int $companyId, ?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'mapping';
        $candidate = $base;
        $i = 1;
        while ($this->mappingRepository->slugExists($companyId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function normalizeCompanyFilter(array &$filters): void
    {
        $identifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! $identifier) {
            return;
        }
        $company = $this->companyRepository->findByIdentifierOrFail((string) $identifier);
        $filters['company_id'] = $company->id;
        unset($filters['company']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function normalizeIntegrationFilter(array &$filters): void
    {
        $identifier = $filters['integration'] ?? $filters['integration_id'] ?? null;
        if (! $identifier || ctype_digit((string) $identifier)) {
            return;
        }
        $integration = $this->integrationRepository->findByIdentifierOrFail((string) $identifier);
        $filters['integration_id'] = $integration->id;
        unset($filters['integration']);
    }
}
