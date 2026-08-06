<?php

namespace App\Domains\Ai\Services;

use App\Domains\Ai\Repositories\AiConversationRepository;
use App\Domains\Ai\Repositories\AiPromptRepository;
use App\Domains\Ai\Repositories\AiProviderRepository;
use App\Domains\Ai\Repositories\AiSettingRepository;
use App\Domains\Ai\Repositories\AiUsageLogRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AiAnalyticsService
{
    public function __construct(
        private readonly AiProviderRepository $providerRepository,
        private readonly AiPromptRepository $promptRepository,
        private readonly AiConversationRepository $conversationRepository,
        private readonly AiUsageLogRepository $usageLogRepository,
        private readonly AiSettingRepository $settingRepository,
        private readonly AiProviderService $providerService,
        private readonly CompanyRepository $companyRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'provider_statistics' => $this->providerRepository->statistics(),
            'prompt_statistics' => $this->promptRepository->statistics(),
            'conversation_statistics' => $this->conversationRepository->statistics(),
            'usage_statistics' => $this->usageLogRepository->statistics(),
            'usage_analytics' => $this->usageLogRepository->analytics(30),
            'catalog' => $this->providerService->catalog(),
            'recent_logs' => $this->usageLogRepository->paginateFiltered(['per_page' => 8])->items(),
            'recent_conversations' => $this->conversationRepository->paginateFiltered(['per_page' => 5])->items(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function usageAnalytics(?int $days = 30): array
    {
        return $this->usageLogRepository->analytics($days);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateLogs(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->usageLogRepository->paginateFiltered($filters);
    }

    public function findLog(string $identifier)
    {
        return $this->usageLogRepository->findByIdentifierOrFail($identifier)
            ->load([
                'user:id,uuid,full_name,email',
                'provider:id,uuid,name,driver,slug',
                'conversation:id,uuid,title,feature',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(?int $companyId = null): array
    {
        $rows = $this->settingRepository->forCompany($companyId);
        $mapped = [];
        foreach ($rows as $row) {
            $mapped[$row->key] = [
                'uuid' => $row->uuid,
                'group' => $row->group,
                'key' => $row->key,
                'value' => $row->value,
            ];
        }

        return [
            'settings' => $mapped,
            'features' => config('ai.features', []),
            'config' => [
                'default_driver' => config('ai.default_driver'),
                'timeout' => config('ai.timeout'),
                'max_tokens' => config('ai.max_tokens'),
                'daily_token_limit' => config('ai.daily_token_limit'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(array $data, ?int $companyId = null): array
    {
        $settings = (array) ($data['settings'] ?? []);
        foreach ($settings as $key => $value) {
            $group = 'general';
            $payload = $value;
            if (is_array($value) && array_key_exists('value', $value)) {
                $group = (string) ($value['group'] ?? 'general');
                $payload = $value['value'];
            }
            $this->settingRepository->upsert((string) $key, $payload, $group, $companyId);
        }

        return $this->settings($companyId);
    }
}
