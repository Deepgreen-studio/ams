<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Enums\AutomationActionType;
use App\Domains\Automation\Models\AutomationAction;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Services\CustomerTaskService;
use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationPriority;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Services\NotificationDispatchService;
use App\Domains\Notifications\Services\NotificationService;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Services\SupportTicketService;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

class AutomationActionExecutor
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
        private readonly NotificationService $notificationService,
        private readonly ?SupportTicketService $supportTicketService = null,
        private readonly ?CustomerTaskService $customerTaskService = null,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    public function execute(AutomationAction $action, array $context, ?User $actor = null): array
    {
        $type = $action->action_type instanceof AutomationActionType
            ? $action->action_type
            : AutomationActionType::from((string) $action->action_type);

        if (! $type->isImplemented()) {
            return [
                'status' => 'skipped',
                'message' => $type->label().' is reserved for a future release.',
            ];
        }

        $config = $action->config ?? [];

        try {
            return match ($type) {
                AutomationActionType::SendEmail => $this->sendEmail($config, $context, $actor),
                AutomationActionType::SendNotification => $this->sendNotification($config, $context, $actor),
                AutomationActionType::CreateTask => $this->createTask($config, $context, $actor),
                AutomationActionType::AssignAgent => $this->assignAgent($config, $context, $actor),
                AutomationActionType::AssignRole => $this->assignRole($config, $context, $actor),
                AutomationActionType::GenerateApiKey => $this->generateApiKey($config, $context, $actor),
                AutomationActionType::NotifyCustomers => $this->notifyCustomers($config, $context, $actor),
                AutomationActionType::SendPush => [
                    'status' => 'skipped',
                    'message' => 'Push notifications are not implemented yet.',
                ],
            };
        } catch (Throwable $exception) {
            Log::warning('Automation action failed', [
                'action' => $type->value,
                'error' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function sendEmail(array $config, array $context, ?User $actor): array
    {
        $eventKey = $this->resolveNotificationEventKey($config, $context);
        $recipients = $this->resolveUsers($config, $context, $actor);

        if ($recipients->isEmpty()) {
            return ['status' => 'skipped', 'message' => 'No email recipients resolved.'];
        }

        $sent = $this->notificationDispatchService->dispatch($eventKey, $recipients, array_merge($context, [
            'priority' => $config['priority'] ?? 'normal',
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->full_name ?? $actor?->name,
        ]));

        return [
            'status' => 'success',
            'message' => "Email/notification dispatch completed for {$sent} recipient(s).",
            'data' => ['recipients' => $sent, 'event_key' => $eventKey->value],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function sendNotification(array $config, array $context, ?User $actor): array
    {
        $recipients = $this->resolveUsers($config, $context, $actor);
        if ($recipients->isEmpty()) {
            return ['status' => 'skipped', 'message' => 'No in-app recipients resolved.'];
        }

        $title = $this->interpolate((string) ($config['title'] ?? 'Automation notification'), $context);
        $message = $this->interpolate((string) ($config['message'] ?? 'An automation rule was triggered.'), $context);
        $created = 0;

        foreach ($recipients as $user) {
            $this->notificationService->create([
                'company_id' => $context['company_id'] ?? null,
                'user_id' => $user->id,
                'channel' => NotificationChannelEnum::InApp->value,
                'template' => $config['template'] ?? ($context['event_key'] ?? 'automation'),
                'event_key' => $context['event_key'] ?? 'automation.rule',
                'title' => $title,
                'message' => $message,
                'status' => NotificationStatus::Sent->value,
                'priority' => NotificationPriority::from((string) ($config['priority'] ?? 'normal'))->value,
                'data' => $context,
                'sent_at' => now(),
            ], $actor);
            $created++;
        }

        return [
            'status' => 'success',
            'message' => "Created {$created} in-app notification(s).",
            'data' => ['created' => $created],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function createTask(array $config, array $context, ?User $actor): array
    {
        if ($this->customerTaskService === null) {
            return ['status' => 'failed', 'message' => 'Customer task service unavailable.'];
        }

        $customerId = $config['customer_id'] ?? $context['customer_uuid'] ?? $context['customer_id'] ?? null;
        if (blank($customerId)) {
            return ['status' => 'skipped', 'message' => 'No customer available to create a task.'];
        }

        $systemActor = $actor ?? User::query()->orderBy('id')->first();
        if (! $systemActor) {
            return ['status' => 'failed', 'message' => 'No actor available to create task.'];
        }

        $task = $this->customerTaskService->create([
            'customer_id' => (string) $customerId,
            'title' => $this->interpolate((string) ($config['title'] ?? 'Automation task'), $context),
            'description' => $this->interpolate((string) ($config['description'] ?? 'Created by automation rule.'), $context),
            'priority' => $config['priority'] ?? 'medium',
            'assigned_to' => $config['assigned_to'] ?? null,
        ], $systemActor);

        return [
            'status' => 'success',
            'message' => 'Customer task created.',
            'data' => ['task_uuid' => $task->uuid],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function assignAgent(array $config, array $context, ?User $actor): array
    {
        if ($this->supportTicketService === null) {
            return ['status' => 'failed', 'message' => 'Support ticket service unavailable.'];
        }

        $ticketUuid = $context['ticket_uuid'] ?? $context['uuid'] ?? null;
        $agent = $config['assigned_to'] ?? $config['agent_id'] ?? null;
        if (blank($ticketUuid) || blank($agent)) {
            return ['status' => 'skipped', 'message' => 'Ticket or agent missing for assign_agent action.'];
        }

        $systemActor = $actor ?? User::query()->orderBy('id')->first();
        if (! $systemActor) {
            return ['status' => 'failed', 'message' => 'No actor available for assignment.'];
        }

        $ticket = $this->supportTicketService->assign((string) $ticketUuid, [
            'assigned_to' => (string) $agent,
        ], $systemActor);

        return [
            'status' => 'success',
            'message' => 'Agent assigned to ticket.',
            'data' => ['ticket_uuid' => $ticket->uuid, 'assigned_to' => $ticket->assigned_to],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function assignRole(array $config, array $context, ?User $actor): array
    {
        $roleName = (string) ($config['role'] ?? 'customer');
        $user = $this->resolvePrimaryUser($config, $context);
        if (! $user) {
            return ['status' => 'skipped', 'message' => 'No user found to assign role.'];
        }

        if (! Role::query()->where('name', $roleName)->exists()) {
            throw new ApiException("Role [{$roleName}] does not exist.", 422);
        }

        if (! $user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }

        return [
            'status' => 'success',
            'message' => "Role [{$roleName}] assigned to {$user->email}.",
            'data' => ['user_uuid' => $user->uuid, 'role' => $roleName],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function generateApiKey(array $config, array $context, ?User $actor): array
    {
        $user = $this->resolvePrimaryUser($config, $context);
        if (! $user) {
            return ['status' => 'skipped', 'message' => 'No user found to generate API key.'];
        }

        $tokenName = (string) ($config['token_name'] ?? 'automation-api-key');
        $plainTextToken = $user->createToken($tokenName)->plainTextToken;

        return [
            'status' => 'success',
            'message' => 'API key generated.',
            'data' => [
                'user_uuid' => $user->uuid,
                'token_name' => $tokenName,
                'token_preview' => Str::of($plainTextToken)->substr(0, 12).'…',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function notifyCustomers(array $config, array $context, ?User $actor): array
    {
        $companyId = $context['company_id'] ?? null;
        $query = User::query()->role('customer');

        if ($companyId) {
            $customerIds = Customer::query()->where('company_id', $companyId)->pluck('id');
            $query->whereIn('customer_id', $customerIds);
        }

        $recipients = $query->limit(200)->get();
        if ($recipients->isEmpty()) {
            return ['status' => 'skipped', 'message' => 'No customer users found to notify.'];
        }

        $created = 0;
        $title = $this->interpolate((string) ($config['title'] ?? 'Application release update'), $context);
        $message = $this->interpolate((string) ($config['message'] ?? 'A new application release is available.'), $context);

        foreach ($recipients as $user) {
            $this->notificationService->create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'channel' => NotificationChannelEnum::InApp->value,
                'template' => 'application.release_deployed',
                'event_key' => $context['event_key'] ?? 'application.release_deployed',
                'title' => $title,
                'message' => $message,
                'status' => NotificationStatus::Sent->value,
                'priority' => NotificationPriority::Normal->value,
                'data' => $context,
                'sent_at' => now(),
            ], $actor);
            $created++;
        }

        return [
            'status' => 'success',
            'message' => "Notified {$created} customer user(s).",
            'data' => ['created' => $created],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    private function resolveNotificationEventKey(array $config, array $context): NotificationEventKey
    {
        $raw = (string) ($config['notification_event_key'] ?? $context['event_key'] ?? NotificationEventKey::TicketCreated->value);

        return NotificationEventKey::tryFrom($raw) ?? NotificationEventKey::TicketCreated;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function resolveUsers(array $config, array $context, ?User $actor)
    {
        if (! empty($config['user_ids']) && is_array($config['user_ids'])) {
            return User::query()->whereIn('id', $config['user_ids'])->get();
        }

        if (! empty($config['user_uuids']) && is_array($config['user_uuids'])) {
            return User::query()->whereIn('uuid', $config['user_uuids'])->get();
        }

        if (! empty($config['email'])) {
            $user = User::query()->where('email', $config['email'])->first();

            return collect($user ? [$user] : []);
        }

        if (! empty($context['assignee_id'])) {
            $user = User::query()->find($context['assignee_id']);

            return collect($user ? [$user] : []);
        }

        if (! empty($context['user_id'])) {
            $user = User::query()->find($context['user_id']);

            return collect($user ? [$user] : []);
        }

        return collect($actor ? [$actor] : []);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    private function resolvePrimaryUser(array $config, array $context): ?User
    {
        return $this->resolveUsers($config, $context, null)->first();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function interpolate(string $content, array $context): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-z0-9_.]+)\s*\}\}/i', function (array $matches) use ($context) {
            $key = strtolower($matches[1]);

            return (string) data_get($context, $key, $context[$key] ?? '');
        }, $content);
    }
}
