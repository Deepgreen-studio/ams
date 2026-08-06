<?php

namespace App\Domains\Ai\Contracts;

use App\Domains\Ai\DTOs\AiCategorizeRequest;
use App\Domains\Ai\DTOs\AiCategorizeResult;
use App\Domains\Ai\DTOs\AiChatRequest;
use App\Domains\Ai\DTOs\AiChatResult;
use App\Domains\Ai\DTOs\AiCompletionRequest;
use App\Domains\Ai\DTOs\AiCompletionResult;
use App\Domains\Ai\DTOs\AiEmbeddingRequest;
use App\Domains\Ai\DTOs\AiEmbeddingResult;
use App\Domains\Ai\DTOs\AiHealthResult;
use App\Domains\Ai\DTOs\AiTextRequest;
use App\Domains\Ai\DTOs\AiTextResult;
use App\Domains\Ai\DTOs\AiTranslateRequest;
use App\Domains\Ai\Models\AiProvider;

interface AiProviderInterface
{
    public function driver(): string;

    public function configure(AiProvider $provider): self;

    public function testConnection(): AiHealthResult;

    public function chat(AiChatRequest $request): AiChatResult;

    public function complete(AiCompletionRequest $request): AiCompletionResult;

    public function embed(AiEmbeddingRequest $request): AiEmbeddingResult;

    public function summarize(AiTextRequest $request): AiTextResult;

    public function translate(AiTranslateRequest $request): AiTextResult;

    public function categorize(AiCategorizeRequest $request): AiCategorizeResult;
}
