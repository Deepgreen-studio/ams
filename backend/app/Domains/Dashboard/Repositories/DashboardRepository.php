<?php

namespace App\Domains\Dashboard\Repositories;

use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Customers\Enums\CustomerTaskPriority;
use App\Domains\Customers\Enums\CustomerTaskStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    /**
     * @return list<string>
     */
    public function openTicketStatuses(): array
    {
        return [
            SupportTicketStatus::Open->value,
            SupportTicketStatus::Pending->value,
            SupportTicketStatus::InProgress->value,
            SupportTicketStatus::WaitingForCustomer->value,
            SupportTicketStatus::Reopened->value,
        ];
    }

    /**
     * @return list<string>
     */
    public function openTaskStatuses(): array
    {
        return [
            CustomerTaskStatus::Open->value,
            CustomerTaskStatus::InProgress->value,
        ];
    }

    public function countApplications(?CarbonInterface $createdFrom = null, ?CarbonInterface $createdTo = null): int
    {
        $query = Application::query();

        if ($createdFrom !== null) {
            $query->where('created_at', '>=', $createdFrom);
        }
        if ($createdTo !== null) {
            $query->where('created_at', '<', $createdTo);
        }

        return (int) $query->count();
    }

    public function countActiveApplications(): int
    {
        return (int) Application::query()
            ->where('status', ApplicationStatus::Active->value)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    public function applicationCountsByStatus(): array
    {
        $rows = Application::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [];
        foreach (ApplicationStatus::cases() as $status) {
            $counts[$status->value] = (int) ($rows[$status->value] ?? 0);
        }

        return $counts;
    }

    public function countCustomers(?CarbonInterface $createdFrom = null, ?CarbonInterface $createdTo = null): int
    {
        $query = Customer::query();

        if ($createdFrom !== null) {
            $query->where('created_at', '>=', $createdFrom);
        }
        if ($createdTo !== null) {
            $query->where('created_at', '<', $createdTo);
        }

        return (int) $query->count();
    }

    public function countOpenTickets(): int
    {
        return (int) SupportTicket::query()
            ->whereIn('status', $this->openTicketStatuses())
            ->count();
    }

    public function countTickets(?CarbonInterface $createdFrom = null, ?CarbonInterface $createdTo = null): int
    {
        $query = SupportTicket::query();

        if ($createdFrom !== null) {
            $query->where('created_at', '>=', $createdFrom);
        }
        if ($createdTo !== null) {
            $query->where('created_at', '<', $createdTo);
        }

        return (int) $query->count();
    }

    public function countActiveUsers(): int
    {
        return (int) User::query()
            ->where('is_active', true)
            ->count();
    }

    public function countUsers(?CarbonInterface $createdFrom = null, ?CarbonInterface $createdTo = null): int
    {
        $query = User::query();

        if ($createdFrom !== null) {
            $query->where('created_at', '>=', $createdFrom);
        }
        if ($createdTo !== null) {
            $query->where('created_at', '<', $createdTo);
        }

        return (int) $query->count();
    }

    /**
     * Recent applications with creator and latest upcoming/non-terminal release.
     *
     * @return Collection<int, Application>
     */
    public function applicationSummary(int $limit = 8): Collection
    {
        return Application::query()
            ->with([
                'creator:id,uuid,first_name,last_name,full_name,name',
                'releases' => function ($query): void {
                    $query->whereNotIn('status', [
                        ApplicationReleaseStatus::Cancelled->value,
                    ])
                        ->orderByRaw('COALESCE(scheduled_at, deployment_date, created_at) desc')
                        ->limit(5);
                },
            ])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Tasks due today plus open important/recent work for the actor.
     *
     * @return array{tasks: Collection<int, CustomerTask>, tickets: Collection<int, SupportTicket>}
     */
    public function todaysWork(?User $actor, int $limit = 12): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $tasks = CustomerTask::query()
            ->with([
                'assignee:id,uuid,first_name,last_name,full_name,name',
                'customer:id,uuid,company_name,first_name,last_name,email',
            ])
            ->whereIn('status', $this->openTaskStatuses())
            ->where(function ($query) use ($todayStart, $todayEnd, $actor): void {
                $query->whereBetween('due_at', [$todayStart, $todayEnd]);

                if ($actor !== null) {
                    $query->orWhere(function ($inner) use ($actor): void {
                        $inner->where('assigned_to', $actor->id)
                            ->whereIn('status', $this->openTaskStatuses());
                    });
                }
            })
            ->orderByRaw('CASE priority WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END', [
                CustomerTaskPriority::Urgent->value,
                CustomerTaskPriority::High->value,
                CustomerTaskPriority::Medium->value,
            ])
            ->orderBy('due_at')
            ->limit($limit)
            ->get();

        $tickets = SupportTicket::query()
            ->whereIn('status', $this->openTicketStatuses())
            ->where(function ($query) use ($actor): void {
                if ($actor !== null) {
                    $query->where('assigned_to', $actor->id)
                        ->orWhereNull('assigned_to');
                }
            })
            ->orderByRaw("CASE priority WHEN 'emergency' THEN 1 WHEN 'critical' THEN 2 WHEN 'high' THEN 3 WHEN 'medium' THEN 4 ELSE 5 END")
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return [
            'tasks' => $tasks,
            'tickets' => $tickets,
        ];
    }

    /**
     * Team members with open ticket + task counts.
     *
     * @return Collection<int, object{id: int, uuid: string, first_name: ?string, last_name: ?string, full_name: ?string, name: ?string, ticket_count: int, task_count: int, open_count: int}>
     */
    public function teamWorkload(int $limit = 10): Collection
    {
        $openTicketStatuses = $this->openTicketStatuses();
        $openTaskStatuses = $this->openTaskStatuses();

        $ticketCounts = SupportTicket::query()
            ->select('assigned_to', DB::raw('COUNT(*) as ticket_count'))
            ->whereNotNull('assigned_to')
            ->whereIn('status', $openTicketStatuses)
            ->groupBy('assigned_to')
            ->pluck('ticket_count', 'assigned_to');

        $taskCounts = CustomerTask::query()
            ->select('assigned_to', DB::raw('COUNT(*) as task_count'))
            ->whereNotNull('assigned_to')
            ->whereIn('status', $openTaskStatuses)
            ->groupBy('assigned_to')
            ->pluck('task_count', 'assigned_to');

        $userIds = $ticketCounts->keys()
            ->merge($taskCounts->keys())
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return User::query()
                ->where('is_active', true)
                ->orderBy('full_name')
                ->limit($limit)
                ->get(['id', 'uuid', 'first_name', 'last_name', 'full_name', 'name'])
                ->map(function (User $user) {
                    return (object) [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name ?: $user->name,
                        'name' => $user->name,
                        'ticket_count' => 0,
                        'task_count' => 0,
                        'open_count' => 0,
                    ];
                });
        }

        return User::query()
            ->whereIn('id', $userIds->all())
            ->orderBy('full_name')
            ->limit($limit)
            ->get(['id', 'uuid', 'first_name', 'last_name', 'full_name', 'name'])
            ->map(function (User $user) use ($ticketCounts, $taskCounts) {
                $tickets = (int) ($ticketCounts[$user->id] ?? 0);
                $tasks = (int) ($taskCounts[$user->id] ?? 0);

                return (object) [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name ?: $user->name,
                    'name' => $user->name,
                    'ticket_count' => $tickets,
                    'task_count' => $tasks,
                    'open_count' => $tickets + $tasks,
                ];
            })
            ->sortByDesc('open_count')
            ->values();
    }

    /**
     * Next release for progress derivation (nullable).
     */
    public function nextReleaseFor(Application $application): ?ApplicationRelease
    {
        /** @var Collection<int, ApplicationRelease> $releases */
        $releases = $application->relationLoaded('releases')
            ? $application->releases
            : collect();

        return $releases->first();
    }
}
