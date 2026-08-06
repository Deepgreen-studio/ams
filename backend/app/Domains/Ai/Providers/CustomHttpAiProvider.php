<?php

namespace App\Domains\Ai\Providers;

use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Shared\Exceptions\ApiException;

/**
 * Future-ready custom OpenAI-compatible HTTP endpoint.
 */
class CustomHttpAiProvider extends OpenAiProvider
{
    public function driver(): string
    {
        return 'custom';
    }

    public function testConnection(): AiHealthResult
    {
        $this->requireConfigured();
        if (blank($this->provider?->base_url)) {
            return new AiHealthResult(false, 'Custom AI requires base_url.', []);
        }

        return parent::testConnection();
    }

    public function chat(AiChatRequest $request): AiChatResult
    {
        if (blank($this->provider?->base_url)) {
            throw new ApiException('Custom AI base_url is required.', 422);
        }

        return parent::chat($request);
    }

    public function embed(AiEmbeddingRequest $request): AiEmbeddingResult
    {
        if (blank($this->provider?->base_url)) {
            throw new ApiException('Custom AI base_url is required.', 422);
        }

        return parent::embed($request);
    }
}
