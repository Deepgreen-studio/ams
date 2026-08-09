<?php

namespace App\Domains\Dashboard\Services;

use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Dashboard\Repositories\DashboardRepository;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(?User $actor, int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $to = Carbon::now()->endOfDay();
        $from = Carbon::now()->subDays($days)->startOfDay();
        $previousFrom = (clone $from)->subDays($days);
        $previousTo = clone $from;

        $applicationsTotal = $this->dashboardRepository->countApplications();
        $applicationsActive = $this->dashboardRepository->countActiveApplications();
        $applicationsCreated = $this->dashboardRepository->countApplications($from, $to);
        $applicationsCreatedPrev = $this->dashboardRepository->countApplications($previousFrom, $previousTo);

        $customersTotal = $this->dashboardRepository->countCustomers();
        $customersCreated = $this->dashboardRepository->countCustomers($from, $to);
        $customersCreatedPrev = $this->dashboardRepository->countCustomers($previousFrom, $previousTo);

        $openTickets = $this->dashboardRepository->countOpenTickets();
        $ticketsCreated = $this->dashboardRepository->countTickets($from, $to);
        $ticketsCreatedPrev = $this->dashboardRepository->countTickets($previousFrom, $previousTo);
        $ticketsTotal = $this->dashboardRepository->countTickets();

        $usersActive = $this->dashboardRepository->countActiveUsers();
        $usersCreated = $this->dashboardRepository->countUsers($from, $to);
        $usersCreatedPrev = $this->dashboardRepository->countUsers($previousFrom, $previousTo);

        $byStatus = $this->dashboardRepository->applicationCountsByStatus();
        $completedLike = ($byStatus[ApplicationStatus::Active->value] ?? 0)
            + ($byStatus[ApplicationStatus::Archived->value] ?? 0);
        $percentCompleted = $applicationsTotal > 0
            ? (int) round(($completedLike / $applicationsTotal) * 100)
            : 0;

        $summaryItems = $this->dashboardRepository
            ->applicationSummary(8)
            ->map(fn (Application $app) => $this->mapApplicationSummary($app))
            ->values()
            ->all();

        $todaysWork = $this->dashboardRepository->todaysWork($actor, 12);
        $taskItems = $todaysWork['tasks']->map(fn (CustomerTask $task) => $this->mapTask($task));
        $ticketItems = $todaysWork['tickets']->map(fn (SupportTicket $ticket) => $this->mapTicket($ticket));

        $combinedTasks = $taskItems
            ->concat($ticketItems)
            ->sortByDesc(fn (array $item) => $item['important'] ? 1 : 0)
            ->values();

        $importantCount = $combinedTasks->where('important', true)->count();
        $taskCount = $taskItems->count();
        $ticketCount = $ticketItems->count();

        $team = $this->dashboardRepository
            ->teamWorkload(10)
            ->map(fn (object $person) => [
                'uuid' => $person->uuid,
                'full_name' => $person->full_name,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'initials' => $this->initials(
                    $person->first_name,
                    $person->last_name,
                    $person->full_name,
                ),
                'open_count' => (int) $person->open_count,
                'ticket_count' => (int) $person->ticket_count,
                'task_count' => (int) $person->task_count,
            ])
            ->values()
            ->all();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
            ],
            'metrics' => [
                $this->metric(
                    key: 'applications',
                    label: 'Applications',
                    value: (string) $applicationsTotal,
                    secondary: $applicationsActive.' active',
                    displayValue: $applicationsActive.'/'.$applicationsTotal,
                    current: $applicationsCreated,
                    previous: $applicationsCreatedPrev,
                ),
                $this->metric(
                    key: 'customers',
                    label: 'Customers',
                    value: (string) $customersTotal,
                    secondary: $customersCreated.' new',
                    displayValue: (string) $customersTotal,
                    current: $customersCreated,
                    previous: $customersCreatedPrev,
                ),
                $this->metric(
                    key: 'open_tickets',
                    label: 'Open tickets',
                    value: (string) $openTickets,
                    secondary: 'of '.$ticketsTotal.' total',
                    displayValue: $openTickets.'/'.$ticketsTotal,
                    current: $ticketsCreated,
                    previous: $ticketsCreatedPrev,
                    invertTrend: true,
                ),
                $this->metric(
                    key: 'users',
                    label: 'Team members',
                    value: (string) $usersActive,
                    secondary: $usersActive.' active',
                    displayValue: (string) $usersActive,
                    current: $usersCreated,
                    previous: $usersCreatedPrev,
                ),
            ],
            'application_summary' => [
                'items' => $summaryItems,
            ],
            'overall_progress' => [
                'percent_completed' => $percentCompleted,
                'total' => $applicationsTotal,
                'by_status' => [
                    'active' => $byStatus[ApplicationStatus::Active->value] ?? 0,
                    'draft' => $byStatus[ApplicationStatus::Draft->value] ?? 0,
                    'inactive' => $byStatus[ApplicationStatus::Inactive->value] ?? 0,
                    'archived' => $byStatus[ApplicationStatus::Archived->value] ?? 0,
                ],
            ],
            'todays_tasks' => [
                'tabs' => [
                    'all' => $combinedTasks->count(),
                    'important' => $importantCount,
                    'tickets' => $ticketCount,
                    'customer_tasks' => $taskCount,
                ],
                'items' => $combinedTasks->take(10)->values()->all(),
            ],
            'team_workload' => [
                'range' => 'open',
                'people' => $team,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metric(
        string $key,
        string $label,
        string $value,
        string $secondary,
        string $displayValue,
        int $current,
        int $previous,
        bool $invertTrend = false,
    ): array {
        $trend = $this->trendPercent($current, $previous);
        $directionUp = $trend >= 0;
        $abs = abs($trend);
        $direction = $directionUp ? 'increase' : 'decrease';

        return [
            'key' => $key,
            'label' => $label,
            'value' => $displayValue,
            'raw_value' => $value,
            'secondary' => $secondary,
            'hint' => 'from last period',
            'trend_percent' => $abs,
            'trend_label' => $abs.'% '.$direction,
            'trend_up' => $directionUp,
            'trend_favorable' => $invertTrend ? ! $directionUp : $directionUp,
            'period_current' => $current,
            'period_previous' => $previous,
        ];
    }

    private function trendPercent(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapApplicationSummary(Application $application): array
    {
        $creator = $application->creator;
        $release = $this->dashboardRepository->nextReleaseFor($application);
        $status = $application->status instanceof ApplicationStatus
            ? $application->status
            : ApplicationStatus::tryFrom((string) $application->status);

        $dueDate = null;
        if ($release !== null) {
            $dueDate = $release->scheduled_at?->toDateString()
                ?? $release->deployment_date?->toDateString();
        }

        return [
            'uuid' => $application->uuid,
            'name' => $application->name,
            'owner' => [
                'uuid' => $creator?->uuid,
                'full_name' => $creator?->full_name ?: ($creator?->name ?: 'Unassigned'),
                'first_name' => $creator?->first_name,
                'last_name' => $creator?->last_name,
                'initials' => $this->initials(
                    $creator?->first_name,
                    $creator?->last_name,
                    $creator?->full_name ?: $creator?->name,
                ),
            ],
            'due_date' => $dueDate,
            'status' => $status?->value ?? (string) $application->status,
            'status_label' => $status?->label() ?? ucfirst((string) $application->status),
            'progress' => $this->progressFor($application, $release),
            'platform' => $application->platform?->value ?? (string) $application->platform,
            'current_version' => $application->current_version,
            'next_release' => $release === null ? null : [
                'uuid' => $release->uuid,
                'version_label' => $release->version_label,
                'status' => $release->status instanceof ApplicationReleaseStatus
                    ? $release->status->value
                    : (string) $release->status,
                'scheduled_at' => $release->scheduled_at?->toIso8601String(),
            ],
        ];
    }

    private function progressFor(Application $application, ?ApplicationRelease $release): int
    {
        if ($release !== null) {
            $status = $release->status instanceof ApplicationReleaseStatus
                ? $release->status
                : ApplicationReleaseStatus::tryFrom((string) $release->status);

            return match ($status) {
                ApplicationReleaseStatus::Deployed => 100,
                ApplicationReleaseStatus::Deploying => 75,
                ApplicationReleaseStatus::Approved => 60,
                ApplicationReleaseStatus::PendingApproval => 50,
                ApplicationReleaseStatus::Scheduled => 40,
                ApplicationReleaseStatus::Planned => 25,
                ApplicationReleaseStatus::Failed => 30,
                ApplicationReleaseStatus::Rejected => 20,
                ApplicationReleaseStatus::RolledBack => 35,
                ApplicationReleaseStatus::Cancelled => 0,
                default => 15,
            };
        }

        $appStatus = $application->status instanceof ApplicationStatus
            ? $application->status
            : ApplicationStatus::tryFrom((string) $application->status);

        return match ($appStatus) {
            ApplicationStatus::Active => 85,
            ApplicationStatus::Archived => 100,
            ApplicationStatus::Inactive => 45,
            ApplicationStatus::Draft => 15,
            default => 10,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTask(CustomerTask $task): array
    {
        $status = $task->status instanceof CustomerTaskStatus
            ? $task->status
            : CustomerTaskStatus::tryFrom((string) $task->status);

        $priority = $task->priority instanceof CustomerTaskPriority
            ? $task->priority
            : CustomerTaskPriority::tryFrom((string) $task->priority);

        $important = in_array($priority, [CustomerTaskPriority::High, CustomerTaskPriority::Urgent], true);

        return [
            'id' => $task->uuid,
            'type' => 'customer_task',
            'title' => $task->title,
            'status' => $status?->value ?? (string) $task->status,
            'status_label' => $status?->label() ?? ucfirst((string) $task->status),
            'done' => $status === CustomerTaskStatus::Completed,
            'important' => $important,
            'due_at' => $task->due_at?->toIso8601String(),
            'assignee_uuid' => $task->assignee?->uuid,
            'customer_uuid' => $task->customer?->uuid,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTicket(SupportTicket $ticket): array
    {
        $status = $ticket->status instanceof SupportTicketStatus
            ? $ticket->status
            : SupportTicketStatus::tryFrom((string) $ticket->status);

        $priority = $ticket->priority instanceof SupportTicketPriority
            ? $ticket->priority
            : SupportTicketPriority::tryFrom((string) $ticket->priority);

        $important = in_array($priority, [
            SupportTicketPriority::High,
            SupportTicketPriority::Critical,
            SupportTicketPriority::Emergency,
        ], true);

        $title = trim((string) ($ticket->ticket_number ? $ticket->ticket_number.': ' : '').($ticket->subject ?? ''));

        return [
            'id' => $ticket->uuid,
            'type' => 'support_ticket',
            'title' => $title !== '' ? $title : 'Support ticket',
            'status' => $status?->value ?? (string) $ticket->status,
            'status_label' => $status?->label() ?? ucfirst((string) $ticket->status),
            'done' => $status?->isTerminal() ?? false,
            'important' => $important,
            'due_at' => null,
            'assignee_uuid' => null,
        ];
    }

    private function initials(?string $firstName, ?string $lastName, ?string $fullName): string
    {
        $first = trim((string) $firstName);
        $last = trim((string) $lastName);

        if ($first !== '' || $last !== '') {
            return strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1)) ?: 'U';
        }

        $full = trim((string) $fullName);
        if ($full === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $full) ?: [];
        if (count($parts) === 1) {
            return strtoupper(mb_substr($parts[0], 0, 2));
        }

        return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
    }
}
