<?php

namespace App\Domains\Ai\Services;

use App\Domains\Ai\Enums\AiPromptStatus;
use App\Domains\Ai\Events\AiPromptCreated;
use App\Domains\Ai\Events\AiPromptDeleted;
use App\Domains\Ai\Events\AiPromptUpdated;
use App\Domains\Ai\Models\AiPrompt;
use App\Domains\Ai\Repositories\AiPromptRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiPromptService
{
    public function __construct(
        private readonly AiPromptRepository $promptRepository,
        private readonly CompanyRepository $companyRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->promptRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): AiPrompt
    {
        return $this->promptRepository->findByIdentifierOrFail($identifier)
            ->load(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AiPrompt
    {
        return DB::transaction(function () use ($data, $actor): AiPrompt {
            $payload = $this->preparePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            /** @var AiPrompt $prompt */
            $prompt = $this->promptRepository->create($payload);
            $fresh = $this->find($prompt->uuid);
            event(new AiPromptCreated($fresh, $actor));

            return $fresh;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): AiPrompt
    {
        return DB::transaction(function () use ($identifier, $data, $actor): AiPrompt {
            $prompt = $this->promptRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (($payload['status'] ?? null) === AiPromptStatus::Published->value
                && ($prompt->status?->value ?? $prompt->status) !== AiPromptStatus::Published->value) {
                $payload['version'] = ((int) $prompt->version) + 1;
            }

            $this->promptRepository->update($prompt->id, $payload);
            $fresh = $this->find($prompt->uuid);
            event(new AiPromptUpdated($fresh, $actor));

            return $fresh;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        $prompt = $this->promptRepository->findByIdentifierOrFail($identifier);
        event(new AiPromptDeleted($prompt, $actor));
        $this->promptRepository->delete($prompt->id);
    }

    public function publish(string $identifier, User $actor): AiPrompt
    {
        return $this->update($identifier, ['status' => AiPromptStatus::Published->value], $actor);
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function render(AiPrompt $prompt, array $variables = []): string
    {
        $template = (string) ($prompt->user_template ?: '');
        foreach ($variables as $key => $value) {
            $template = str_replace(['{{'.$key.'}}', '{'.$key.'}'], $value, $template);
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $payload = [];

        foreach ([
            'key', 'name', 'feature', 'system_prompt', 'user_template', 'model_override', 'status',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('company_id', $data)) {
            $companyId = $data['company_id'];
            if ($companyId === null || $companyId === '') {
                $payload['company_id'] = null;
            } elseif (is_numeric($companyId)) {
                $payload['company_id'] = (int) $companyId;
            } else {
                $payload['company_id'] = $this->companyRepository
                    ->findByIdentifierOrFail((string) $companyId)->id;
            }
        }

        if (array_key_exists('temperature', $data) && $data['temperature'] !== null) {
            $payload['temperature'] = (float) $data['temperature'];
        }
        if (array_key_exists('max_tokens', $data) && $data['max_tokens'] !== null) {
            $payload['max_tokens'] = (int) $data['max_tokens'];
        }
        if (array_key_exists('output_schema', $data)) {
            $payload['output_schema'] = is_array($data['output_schema']) ? $data['output_schema'] : null;
        }
        if (array_key_exists('metadata', $data)) {
            $payload['metadata'] = is_array($data['metadata']) ? $data['metadata'] : null;
        }
        if (array_key_exists('version', $data) && $data['version'] !== null) {
            $payload['version'] = (int) $data['version'];
        }

        if (! $isUpdate) {
            if (empty($payload['name']) || empty($payload['feature'])) {
                throw new ApiException('Prompt name and feature are required.', 422);
            }
            $payload['key'] = $payload['key'] ?? Str::slug($payload['name'], '_');
            $payload['status'] = $payload['status'] ?? AiPromptStatus::Draft->value;
            $payload['version'] = $payload['version'] ?? 1;
        }

        return $payload;
    }
}
