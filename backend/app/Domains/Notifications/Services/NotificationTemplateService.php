<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationTemplateApprovalStatus;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Domains\Notifications\Events\NotificationTemplateApproved;
use App\Domains\Notifications\Events\NotificationTemplateCreated;
use App\Domains\Notifications\Events\NotificationTemplateDeleted;
use App\Domains\Notifications\Events\NotificationTemplatePublished;
use App\Domains\Notifications\Events\NotificationTemplateRejected;
use App\Domains\Notifications\Events\NotificationTemplateSubmitted;
use App\Domains\Notifications\Events\NotificationTemplateUpdated;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Models\NotificationTemplateVersion;
use App\Domains\Notifications\Notifications\TemplatedNotification;
use App\Domains\Notifications\Repositories\NotificationTemplateApprovalRepository;
use App\Domains\Notifications\Repositories\NotificationTemplateRepository;
use App\Domains\Notifications\Repositories\NotificationTemplateVersionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

class NotificationTemplateService
{
    /**
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'es', 'fr', 'de', 'ar', 'bn', 'hi', 'zh'];

    public function __construct(
        private readonly NotificationTemplateRepository $templateRepository,
        private readonly NotificationTemplateVersionRepository $versionRepository,
        private readonly NotificationTemplateApprovalRepository $approvalRepository,
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

        return $this->templateRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): NotificationTemplate
    {
        return $this->templateRepository->findByIdentifierOrFail($identifier)
            ->load(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    public function resolve(NotificationEventKey $eventKey, NotificationChannelEnum $channel, string $locale = 'en'): ?NotificationTemplate
    {
        return $this->templateRepository->resolveActive($eventKey->value, $channel->value, $locale);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject: ?string, body: string, title: ?string, locale: string, channel: string, template_uuid: ?string}
     */
    public function render(NotificationEventKey $eventKey, NotificationChannelEnum $channel, array $variables, string $locale = 'en'): array
    {
        $template = $this->resolve($eventKey, $channel, $locale);
        $defaults = $this->defaultContent($eventKey, $channel);
        $subject = $template?->subject ?: $defaults['subject'];
        $body = $template?->body ?: $defaults['body'];

        return [
            'subject' => $this->interpolate($subject, $variables),
            'body' => $this->interpolate($body, $variables),
            'title' => $this->interpolate($defaults['title'], $variables),
            'locale' => $template?->locale ?? $locale,
            'channel' => $channel->value,
            'template_uuid' => $template?->uuid,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): NotificationTemplate
    {
        return DB::transaction(function () use ($data, $actor): NotificationTemplate {
            $eventKey = NotificationEventKey::from((string) $data['event_key']);
            $channel = NotificationChannelEnum::from((string) $data['channel']);
            $locale = $this->normalizeLocale((string) ($data['locale'] ?? 'en'));

            /** @var NotificationTemplate $template */
            $template = $this->templateRepository->create([
                'company_id' => $this->resolveCompanyId($data['company_id'] ?? null),
                'event_key' => $eventKey->value,
                'channel' => $channel->value,
                'locale' => $locale,
                'name' => trim((string) $data['name']),
                'subject' => $data['subject'] ?? null,
                'body' => (string) $data['body'],
                'available_variables' => $data['available_variables'] ?? $eventKey->defaultVariables(),
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'is_system' => false,
                'priority' => NotificationPriority::from((string) ($data['priority'] ?? NotificationPriority::Normal->value))->value,
                'workflow_status' => NotificationTemplateStatus::Draft->value,
                'current_version' => 1,
                'change_summary' => $data['change_summary'] ?? 'Initial version',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($template, (string) ($data['change_summary'] ?? 'Initial version'), $actor->id);
            event(new NotificationTemplateCreated($template, $actor));

            return $this->find($template->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): NotificationTemplate
    {
        return DB::transaction(function () use ($identifier, $data, $actor): NotificationTemplate {
            $template = $this->templateRepository->findByIdentifierOrFail($identifier);

            if ($template->is_system && array_key_exists('event_key', $data)) {
                throw new ApiException('System templates cannot change event key.', 422);
            }

            $payload = ['updated_by' => $actor->id];
            foreach (['name', 'subject', 'body', 'available_variables', 'is_active', 'change_summary'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }
            if (array_key_exists('channel', $data)) {
                $payload['channel'] = NotificationChannelEnum::from((string) $data['channel'])->value;
            }
            if (array_key_exists('priority', $data)) {
                $payload['priority'] = NotificationPriority::from((string) $data['priority'])->value;
            }
            if (array_key_exists('locale', $data)) {
                $payload['locale'] = $this->normalizeLocale((string) $data['locale']);
            }
            if (array_key_exists('company_id', $data)) {
                $payload['company_id'] = $this->resolveCompanyId($data['company_id']);
            }

            // Content edits return published templates to draft.
            if ($template->workflow_status === NotificationTemplateStatus::Published
                && $this->touchesContent($data)) {
                $payload['workflow_status'] = NotificationTemplateStatus::Draft->value;
                $payload['published_at'] = null;
            }

            $nextVersion = max(
                $template->current_version + 1,
                $this->versionRepository->nextVersionNumber($template->id)
            );
            $payload['current_version'] = $nextVersion;

            /** @var NotificationTemplate $updated */
            $updated = $this->templateRepository->update($template->id, $payload);
            $this->recordVersion(
                $updated,
                (string) ($data['change_summary'] ?? $data['reason'] ?? 'Template updated'),
                $actor->id
            );
            event(new NotificationTemplateUpdated($updated, $actor));

            return $this->find($updated->uuid);
        });
    }

    public function delete(string $identifier, ?User $actor = null): void
    {
        $template = $this->templateRepository->findByIdentifierOrFail($identifier);
        if ($template->is_system) {
            throw new ApiException('System templates cannot be deleted. Deactivate them instead.', 422);
        }

        $this->templateRepository->delete($template->id);

        if ($actor) {
            event(new NotificationTemplateDeleted($template, $actor));
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject: ?string, body: string, title: ?string, channel: string, locale: string, variables: array<string, mixed>}
     */
    public function preview(string $identifier, array $variables = []): array
    {
        $template = $this->find($identifier);
        $defaults = $this->sampleVariables($template);
        $merged = array_change_key_case(array_merge($defaults, $variables), CASE_LOWER);

        return [
            'subject' => $this->interpolate($template->subject, $merged),
            'body' => $this->interpolate($template->body, $merged),
            'title' => $this->interpolate($template->name, $merged),
            'channel' => $template->channel?->value ?? (string) $template->channel,
            'locale' => $template->locale,
            'variables' => $merged,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{sent: bool, channel: string, recipient: string, preview: array<string, mixed>}
     */
    public function testSend(string $identifier, User $actor, array $payload = []): array
    {
        $template = $this->find($identifier);
        $preview = $this->preview($identifier, $payload['variables'] ?? []);
        $channel = $template->channel instanceof NotificationChannelEnum
            ? $template->channel
            : NotificationChannelEnum::from((string) $template->channel);

        $laravelChannel = $channel->laravelChannel();
        if ($laravelChannel === null) {
            throw new ApiException('Test send is not available for this channel yet.', 422);
        }

        $recipientEmail = (string) ($payload['email'] ?? $actor->email);
        if ($laravelChannel === 'mail' && blank($recipientEmail)) {
            throw new ApiException('A recipient email is required for email test send.', 422);
        }

        $eventKey = $template->event_key instanceof NotificationEventKey
            ? $template->event_key
            : NotificationEventKey::from((string) $template->event_key);

        $notifiable = $laravelChannel === 'mail'
            ? NotificationFacade::route('mail', $recipientEmail)
            : $actor;

        NotificationFacade::send($notifiable, new TemplatedNotification(
            eventKey: $eventKey,
            laravelChannels: [$laravelChannel],
            payload: $preview['variables'],
            databaseData: [
                'type' => $eventKey->value,
                'event_key' => $eventKey->value,
                'title' => $preview['title'],
                'body' => strip_tags((string) $preview['body']),
                'is_test' => true,
            ],
            mailSubject: '[TEST] '.($preview['subject'] ?: $preview['title']),
            mailBody: $preview['body'],
            inAppTitle: '[TEST] '.($preview['title'] ?: $eventKey->label()),
            inAppBody: strip_tags((string) $preview['body']),
        ));

        return [
            'sent' => true,
            'channel' => $channel->value,
            'recipient' => $laravelChannel === 'mail' ? $recipientEmail : (string) $actor->email,
            'preview' => $preview,
        ];
    }

    /**
     * @return Collection<int, NotificationTemplateVersion>
     */
    public function versions(string $identifier): Collection
    {
        $template = $this->templateRepository->findByIdentifierOrFail($identifier);

        return $this->versionRepository->listForTemplate($template->id);
    }

    public function showVersion(string $templateIdentifier, string $versionIdentifier): NotificationTemplateVersion
    {
        $template = $this->templateRepository->findByIdentifierOrFail($templateIdentifier);

        return $this->versionRepository->findForTemplate($template->id, $versionIdentifier)
            ->load('creator:id,uuid,full_name,email');
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(string $identifier, string $from, string $to): array
    {
        $template = $this->templateRepository->findByIdentifierOrFail($identifier);
        $fromVersion = $this->versionRepository->findForTemplate($template->id, $from);
        $toVersion = $this->versionRepository->findForTemplate($template->id, $to);

        $fromSnapshot = $fromVersion->snapshot ?? $this->buildSnapshot($fromVersion);
        $toSnapshot = $toVersion->snapshot ?? $this->buildSnapshot($toVersion);
        $keys = collect(array_keys($fromSnapshot))->merge(array_keys($toSnapshot))->unique()->sort()->values();

        $changes = [];
        foreach ($keys as $key) {
            $left = $this->normalizeComparable($fromSnapshot[$key] ?? null);
            $right = $this->normalizeComparable($toSnapshot[$key] ?? null);
            if ($left !== $right) {
                $changes[$key] = [
                    'from' => $fromSnapshot[$key] ?? null,
                    'to' => $toSnapshot[$key] ?? null,
                ];
            }
        }

        return [
            'from' => $fromVersion,
            'to' => $toVersion,
            'comparison' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
            ],
        ];
    }

    public function restoreVersion(string $identifier, string $versionIdentifier, User $actor, ?string $reason = null): NotificationTemplate
    {
        return DB::transaction(function () use ($identifier, $versionIdentifier, $actor, $reason): NotificationTemplate {
            $template = $this->templateRepository->findByIdentifierOrFail($identifier);
            $version = $this->versionRepository->findForTemplate($template->id, $versionIdentifier);

            $nextVersion = max(
                $template->current_version + 1,
                $this->versionRepository->nextVersionNumber($template->id)
            );

            /** @var NotificationTemplate $updated */
            $updated = $this->templateRepository->update($template->id, [
                'name' => $version->name,
                'subject' => $version->subject,
                'body' => $version->body,
                'channel' => $version->channel?->value ?? $version->channel,
                'locale' => $version->locale,
                'available_variables' => $version->available_variables,
                'priority' => $version->priority?->value ?? $version->priority,
                'workflow_status' => NotificationTemplateStatus::Draft->value,
                'published_at' => null,
                'current_version' => $nextVersion,
                'change_summary' => $reason ?: 'Restored from v'.$version->version,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion(
                $updated,
                $reason ?: 'Restored from v'.$version->version,
                $actor->id,
                isRestore: true,
                restoredFrom: $version->version
            );

            event(new NotificationTemplateUpdated($updated, $actor));

            return $this->find($updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submitForReview(string $identifier, User $actor, array $data = []): NotificationTemplate
    {
        return DB::transaction(function () use ($identifier, $actor, $data): NotificationTemplate {
            $template = $this->templateRepository->findByIdentifierOrFail($identifier);
            $this->assertTransition($template->workflow_status, NotificationTemplateStatus::Review);

            $nextVersion = max(
                $template->current_version + 1,
                $this->versionRepository->nextVersionNumber($template->id)
            );

            /** @var NotificationTemplate $updated */
            $updated = $this->templateRepository->update($template->id, [
                'workflow_status' => NotificationTemplateStatus::Review->value,
                'current_version' => $nextVersion,
                'change_summary' => $data['comments'] ?? 'Submitted for review',
                'updated_by' => $actor->id,
            ]);

            $version = $this->recordVersion($updated, 'Submitted for review', $actor->id);
            $this->approvalRepository->cancelPendingForTemplate($updated->id);
            $this->approvalRepository->create([
                'notification_template_id' => $updated->id,
                'notification_template_version_id' => $version->id,
                'status' => NotificationTemplateApprovalStatus::Pending->value,
                'requested_by' => $actor->id,
                'comments' => $data['comments'] ?? null,
                'requested_at' => now(),
            ]);

            event(new NotificationTemplateSubmitted($updated, $actor));

            return $this->find($updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(string $approvalIdentifier, User $actor, array $data = []): NotificationTemplate
    {
        return DB::transaction(function () use ($approvalIdentifier, $actor, $data): NotificationTemplate {
            $approval = $this->approvalRepository->findByIdentifierOrFail($approvalIdentifier);
            if ($approval->status !== NotificationTemplateApprovalStatus::Pending) {
                throw new ApiException('Only pending approvals can be decided.', 422);
            }

            $template = $this->templateRepository->findByIdentifierOrFail((string) $approval->template->uuid);
            $this->assertTransition($template->workflow_status, NotificationTemplateStatus::Approved);

            $this->approvalRepository->update($approval->id, [
                'status' => NotificationTemplateApprovalStatus::Approved->value,
                'reviewed_by' => $actor->id,
                'comments' => $data['comments'] ?? $approval->comments,
                'decided_at' => now(),
            ]);

            $nextVersion = max(
                $template->current_version + 1,
                $this->versionRepository->nextVersionNumber($template->id)
            );

            /** @var NotificationTemplate $updated */
            $updated = $this->templateRepository->update($template->id, [
                'workflow_status' => NotificationTemplateStatus::Approved->value,
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Template approved', $actor->id);
            event(new NotificationTemplateApproved($updated, $actor));

            return $this->find($updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reject(string $approvalIdentifier, User $actor, array $data = []): NotificationTemplate
    {
        return DB::transaction(function () use ($approvalIdentifier, $actor, $data): NotificationTemplate {
            $approval = $this->approvalRepository->findByIdentifierOrFail($approvalIdentifier);
            if ($approval->status !== NotificationTemplateApprovalStatus::Pending) {
                throw new ApiException('Only pending approvals can be decided.', 422);
            }

            $template = $this->templateRepository->findByIdentifierOrFail((string) $approval->template->uuid);

            $this->approvalRepository->update($approval->id, [
                'status' => NotificationTemplateApprovalStatus::Rejected->value,
                'reviewed_by' => $actor->id,
                'comments' => $data['comments'] ?? null,
                'decided_at' => now(),
            ]);

            $nextVersion = max(
                $template->current_version + 1,
                $this->versionRepository->nextVersionNumber($template->id)
            );

            /** @var NotificationTemplate $updated */
            $updated = $this->templateRepository->update($template->id, [
                'workflow_status' => NotificationTemplateStatus::Draft->value,
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Template rejected', $actor->id);
            event(new NotificationTemplateRejected($updated, $actor));

            return $this->find($updated->uuid);
        });
    }

    public function publish(string $identifier, User $actor): NotificationTemplate
    {
        return DB::transaction(function () use ($identifier, $actor): NotificationTemplate {
            $template = $this->templateRepository->findByIdentifierOrFail($identifier);
            $this->assertTransition($template->workflow_status, NotificationTemplateStatus::Published);

            $nextVersion = max(
                $template->current_version + 1,
                $this->versionRepository->nextVersionNumber($template->id)
            );

            /** @var NotificationTemplate $updated */
            $updated = $this->templateRepository->update($template->id, [
                'workflow_status' => NotificationTemplateStatus::Published->value,
                'is_active' => true,
                'published_at' => now(),
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Template published', $actor->id);
            event(new NotificationTemplatePublished($updated, $actor));

            return $this->find($updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateApprovals(array $filters = []): LengthAwarePaginator
    {
        return $this->approvalRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function interpolate(?string $content, array $variables): ?string
    {
        if ($content === null) {
            return null;
        }

        return (string) preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $matches) use ($variables) {
            $key = strtolower($matches[1]);

            return (string) ($variables[$key] ?? '');
        }, $content);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function locales(): array
    {
        $labels = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'ar' => 'Arabic',
            'bn' => 'Bengali',
            'hi' => 'Hindi',
            'zh' => 'Chinese',
        ];

        return collect(self::SUPPORTED_LOCALES)->map(fn (string $locale) => [
            'value' => $locale,
            'label' => $labels[$locale] ?? strtoupper($locale),
        ])->values()->all();
    }

    private function recordVersion(
        NotificationTemplate $template,
        string $reason,
        ?int $actorId,
        bool $isRestore = false,
        ?int $restoredFrom = null,
    ): NotificationTemplateVersion {
        $fresh = $template->fresh() ?? $template;

        /** @var NotificationTemplateVersion $version */
        $version = $this->versionRepository->create([
            'notification_template_id' => $fresh->id,
            'version' => $fresh->current_version,
            'status' => $fresh->workflow_status?->value ?? NotificationTemplateStatus::Draft->value,
            'name' => $fresh->name,
            'channel' => $fresh->channel?->value ?? $fresh->channel,
            'locale' => $fresh->locale,
            'event_key' => $fresh->event_key?->value ?? $fresh->event_key,
            'subject' => $fresh->subject,
            'body' => $fresh->body,
            'available_variables' => $fresh->available_variables,
            'priority' => $fresh->priority?->value ?? $fresh->priority,
            'snapshot' => $this->buildSnapshot($fresh),
            'reason' => $reason,
            'is_restore' => $isRestore,
            'restored_from_version' => $restoredFrom,
            'created_by' => $actorId,
            'created_at' => now(),
        ]);

        return $version;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(NotificationTemplate|NotificationTemplateVersion $source): array
    {
        return [
            'name' => $source->name,
            'channel' => $source->channel?->value ?? $source->channel,
            'locale' => $source->locale,
            'event_key' => $source->event_key?->value ?? $source->event_key,
            'subject' => $source->subject,
            'body' => $source->body,
            'available_variables' => $source->available_variables,
            'priority' => $source->priority?->value ?? $source->priority,
            'workflow_status' => $source instanceof NotificationTemplate
                ? ($source->workflow_status?->value ?? $source->workflow_status)
                : ($source->status?->value ?? $source->status),
        ];
    }

    private function normalizeComparable(mixed $value): string
    {
        if (is_array($value)) {
            return (string) json_encode($value);
        }

        return (string) $value;
    }

    private function assertTransition(?NotificationTemplateStatus $from, NotificationTemplateStatus $to): void
    {
        $current = $from ?? NotificationTemplateStatus::Draft;
        if (! $current->canTransitionTo($to)) {
            throw new ApiException(
                "Cannot transition template from {$current->label()} to {$to->label()}.",
                422
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function touchesContent(array $data): bool
    {
        foreach (['name', 'subject', 'body', 'channel', 'locale', 'available_variables', 'priority'] as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    private function resolveCompanyId(mixed $companyIdentifier): ?int
    {
        if (blank($companyIdentifier)) {
            return null;
        }

        return $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier)->id;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            throw new ApiException('Unsupported locale.', 422);
        }

        return $locale;
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleVariables(NotificationTemplate $template): array
    {
        $defaults = [
            'recipient_name' => 'Alex Morgan',
            'ticket_number' => 'T-1001',
            'ticket_uuid' => (string) Str::uuid(),
            'subject' => 'Sample notification subject',
            'priority' => 'high',
            'status' => 'open',
            'actor_name' => 'Jordan Lee',
            'from_status' => 'open',
            'to_status' => 'in_progress',
            'message_preview' => 'This is a sample message preview.',
            'sla_metric' => 'first_response',
            'escalation_level' => '1',
            'ticket_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/').'/support/tickets/sample',
        ];

        $keys = $template->available_variables ?? [];
        if ($template->event_key instanceof NotificationEventKey) {
            $keys = array_values(array_unique(array_merge($keys, $template->event_key->defaultVariables())));
        }

        $sample = [];
        foreach ($keys as $key) {
            $sample[$key] = $defaults[$key] ?? 'sample_'.$key;
        }

        return array_merge($defaults, $sample);
    }

    /**
     * @return array{subject: string, body: string, title: string}
     */
    private function defaultContent(NotificationEventKey $eventKey, NotificationChannelEnum $channel): array
    {
        $title = $eventKey->label();
        $subject = match ($eventKey) {
            NotificationEventKey::TicketCreated => 'New support ticket {{ticket_number}}',
            NotificationEventKey::TicketAssigned => 'Ticket assigned: {{ticket_number}}',
            NotificationEventKey::ReplyAdded => 'New reply on {{ticket_number}}',
            NotificationEventKey::StatusChanged => 'Status updated: {{ticket_number}}',
            NotificationEventKey::TicketClosed => 'Ticket closed: {{ticket_number}}',
            NotificationEventKey::SlaWarning => 'SLA warning: {{ticket_number}}',
            NotificationEventKey::Escalation => 'SLA escalation: {{ticket_number}}',
        };

        $body = match ($eventKey) {
            NotificationEventKey::TicketCreated => '<p>A new support ticket <strong>{{ticket_number}}</strong> was created.</p><p>Subject: {{subject}}</p><p>Priority: {{priority}}</p><p>By: {{actor_name}}</p>',
            NotificationEventKey::TicketAssigned => '<p>Ticket <strong>{{ticket_number}}</strong> was assigned to you.</p><p>Subject: {{subject}}</p><p>Assigned by: {{actor_name}}</p>',
            NotificationEventKey::ReplyAdded => '<p>A new reply was added to <strong>{{ticket_number}}</strong>.</p><p>{{message_preview}}</p><p>By: {{actor_name}}</p>',
            NotificationEventKey::StatusChanged => '<p>Ticket <strong>{{ticket_number}}</strong> status changed from {{from_status}} to {{to_status}}.</p><p>By: {{actor_name}}</p>',
            NotificationEventKey::TicketClosed => '<p>Ticket <strong>{{ticket_number}}</strong> was closed by {{actor_name}}.</p><p>Subject: {{subject}}</p>',
            NotificationEventKey::SlaWarning => '<p>SLA for ticket <strong>{{ticket_number}}</strong> is at risk ({{sla_metric}}).</p><p>Subject: {{subject}}</p>',
            NotificationEventKey::Escalation => '<p>SLA escalation ({{escalation_level}}) raised for <strong>{{ticket_number}}</strong>.</p><p>Metric: {{sla_metric}}</p>',
        };

        if (in_array($channel, [
            NotificationChannelEnum::InApp,
            NotificationChannelEnum::Sms,
            NotificationChannelEnum::Push,
        ], true)) {
            $body = Str::of(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', $body)))->squish()->toString();
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'title' => $title,
        ];
    }
}
