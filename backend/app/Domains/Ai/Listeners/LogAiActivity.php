<?php

namespace App\Domains\Ai\Listeners;

use App\Domains\Ai\Events\AiProviderCreated;
use App\Domains\Ai\Events\AiProviderDeleted;
use App\Domains\Ai\Events\AiProviderUpdated;
use App\Domains\Ai\Events\AiPromptCreated;
use App\Domains\Ai\Events\AiPromptDeleted;
use App\Domains\Ai\Events\AiPromptUpdated;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LogAiActivity
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    public function handleAiProviderCreated(AiProviderCreated $event): void
    {
        $this->log($event->actor, $event->provider, 'ai_provider_created', 'AI provider created');
    }

    public function handleAiProviderUpdated(AiProviderUpdated $event): void
    {
        $this->log($event->actor, $event->provider, 'ai_provider_updated', 'AI provider updated');
    }

    public function handleAiProviderDeleted(AiProviderDeleted $event): void
    {
        $this->log($event->actor, $event->provider, 'ai_provider_deleted', 'AI provider deleted');
    }

    public function handleAiPromptCreated(AiPromptCreated $event): void
    {
        $this->log($event->actor, $event->prompt, 'ai_prompt_created', 'AI prompt created');
    }

    public function handleAiPromptUpdated(AiPromptUpdated $event): void
    {
        $this->log($event->actor, $event->prompt, 'ai_prompt_updated', 'AI prompt updated');
    }

    public function handleAiPromptDeleted(AiPromptDeleted $event): void
    {
        $this->log($event->actor, $event->prompt, 'ai_prompt_deleted', 'AI prompt deleted');
    }

    private function log(User $actor, Model $subject, string $event, string $description): void
    {
        activity('ai')
            ->causedBy($actor)
            ->performedOn($subject)
            ->withProperties([
                'ip' => $this->request?->ip(),
                'user_agent' => $this->request?->userAgent(),
            ])
            ->event($event)
            ->log($description);
    }
}
