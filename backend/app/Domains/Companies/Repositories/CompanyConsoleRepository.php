<?php

namespace App\Domains\Companies\Repositories;

use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVersionStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Models\ApplicationVersion;
use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\CompanyLocation;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Collection;

class CompanyConsoleRepository
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
    public function elevatedPriorities(): array
    {
        return [
            SupportTicketPriority::High->value,
            SupportTicketPriority::Critical->value,
            SupportTicketPriority::Emergency->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $companyId, int $limit = 6): array
    {
        $applicationIds = Application::query()
            ->where('company_id', $companyId)
            ->pluck('id');

        return [
            'applications_by_status' => $this->groupedCount(Application::query()->where('company_id', $companyId), 'status'),
            'applications_by_platform' => $this->platformCounts($companyId),
            'environments_by_health' => $applicationIds->isEmpty()
                ? collect()
                : $this->groupedCount(ApplicationEnvironment::query()->whereIn('application_id', $applicationIds), 'health_status'),
            'environments_by_status' => $applicationIds->isEmpty()
                ? collect()
                : $this->groupedCount(ApplicationEnvironment::query()->whereIn('application_id', $applicationIds), 'status'),
            'versions_by_status' => $applicationIds->isEmpty()
                ? collect()
                : $this->groupedCount(ApplicationVersion::query()->whereIn('application_id', $applicationIds), 'status'),
            'releases_by_status' => $applicationIds->isEmpty()
                ? collect()
                : $this->groupedCount(ApplicationRelease::query()->whereIn('application_id', $applicationIds), 'status'),
            'open_issues' => SupportTicket::query()
                ->where('company_id', $companyId)
                ->whereIn('status', $this->openTicketStatuses())
                ->count(),
            'elevated_issues' => SupportTicket::query()
                ->where('company_id', $companyId)
                ->whereIn('status', $this->openTicketStatuses())
                ->whereIn('priority', $this->elevatedPriorities())
                ->count(),
            'integrations_by_health' => $this->groupedCount(Integration::query()->where('company_id', $companyId), 'health_status'),
            'integrations_by_status' => $this->groupedCount(Integration::query()->where('company_id', $companyId), 'status'),
            'notifications_by_status' => $this->groupedCount(Notification::query()->where('company_id', $companyId), 'status'),
            'unread_notifications' => Notification::query()
                ->where('company_id', $companyId)
                ->whereNull('read_at')
                ->where('status', NotificationStatus::Sent->value)
                ->count(),
            'applications' => $this->applications($companyId, $limit),
            'environments' => $this->environments($applicationIds, $limit),
            'versions' => $this->versions($applicationIds, $limit),
            'releases' => $this->releases($applicationIds, $limit),
            'tickets' => $this->tickets($companyId, $limit),
            'integrations' => $this->integrations($companyId, $limit),
            'notifications' => $this->notifications($companyId, $limit),
            'pending_releases' => $this->pendingReleases($companyId),
            'active_users' => $this->activeUsers($companyId),
            'compliance_cases' => $this->complianceCases($companyId),
            'organization' => $this->organization($companyId),
            'activity' => $this->activity($companyId),
        ];
    }

    private function pendingReleases(int $companyId): int
    {
        $pending = [
            ApplicationReleaseStatus::Planned->value,
            ApplicationReleaseStatus::Scheduled->value,
            ApplicationReleaseStatus::PendingApproval->value,
            ApplicationReleaseStatus::Approved->value,
            ApplicationReleaseStatus::Deploying->value,
        ];

        return ApplicationRelease::query()
            ->whereIn('application_id', Application::query()->where('company_id', $companyId)->select('id'))
            ->whereIn('status', $pending)
            ->count();
    }

    private function activeUsers(int $companyId): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('companies', function ($query) use ($companyId): void {
                $query->where('companies.id', $companyId)->where('company_user.status', 'active');
            })
            ->count();
    }

    private function complianceCases(int $companyId): int
    {
        $privacy = PrivacyRequest::query()
            ->where('company_id', $companyId)
            ->whereIn('status', PrivacyRequestStatus::activeValues())
            ->count();
        $breaches = DataBreach::query()
            ->where('company_id', $companyId)
            ->whereIn('status', DataBreachStatus::activeValues())
            ->count();

        return $privacy + $breaches;
    }

    /**
     * @return array<string, mixed>
     */
    private function organization(int $companyId): array
    {
        $departments = Department::query()
            ->where('company_id', $companyId)
            ->with(['teams' => fn ($query) => $query->with('manager:id,uuid,full_name,email')])
            ->orderBy('name')
            ->get();

        $departmentIds = $departments->pluck('id');
        $users = User::query()
            ->where(function ($query) use ($companyId, $departmentIds): void {
                $query->whereHas('companies', fn ($membership) => $membership->where('companies.id', $companyId))
                    ->orWhereIn('department_id', $departmentIds);
            })
            ->with([
                'department:id,uuid,name',
                'team:id,uuid,name,department_id',
                'location:id,uuid,branch_name',
            ])
            ->orderBy('full_name')
            ->get();

        return [
            'departments' => $departments,
            'locations' => CompanyLocation::query()->where('company_id', $companyId)->orderBy('branch_name')->get(),
            'users' => $users,
        ];
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    private function activity(int $companyId): Collection
    {
        $applicationIds = Application::query()->where('company_id', $companyId)->pluck('id');
        $departmentIds = Department::query()->where('company_id', $companyId)->pluck('id');
        $teamIds = Team::query()->where('company_id', $companyId)->pluck('id');
        $locationIds = CompanyLocation::query()->where('company_id', $companyId)->pluck('id');
        $integrationIds = Integration::query()->where('company_id', $companyId)->pluck('id');
        $ticketIds = SupportTicket::query()->where('company_id', $companyId)->pluck('id');
        $releaseIds = $applicationIds->isEmpty()
            ? collect()
            : ApplicationRelease::query()->whereIn('application_id', $applicationIds)->pluck('id');

        $scopes = [
            Company::class => collect([$companyId]),
            Application::class => $applicationIds,
            Department::class => $departmentIds,
            Team::class => $teamIds,
            CompanyLocation::class => $locationIds,
            Integration::class => $integrationIds,
            SupportTicket::class => $ticketIds,
            ApplicationRelease::class => $releaseIds,
        ];

        return ActivityLog::query()
            ->with('causer:id,uuid,full_name,email')
            ->where(function ($query) use ($scopes): void {
                $started = false;
                foreach ($scopes as $type => $ids) {
                    if ($ids->isEmpty()) {
                        continue;
                    }
                    $method = $started ? 'orWhere' : 'where';
                    $query->{$method}(function ($builder) use ($type, $ids): void {
                        $builder->where('subject_type', $type)->whereIn('subject_id', $ids);
                    });
                    $started = true;
                }
            })
            ->latest('id')
            ->limit(30)
            ->get();
    }

    /**
     * @return Collection<string, int>
     */
    private function groupedCount($query, string $column): Collection
    {
        return $query
            ->selectRaw($column.' as bucket, COUNT(*) as total')
            ->groupBy($column)
            ->pluck('total', 'bucket')
            ->map(fn ($total): int => (int) $total);
    }

    /**
     * @return Collection<int, object>
     */
    private function platformCounts(int $companyId): Collection
    {
        return Application::query()
            ->where('company_id', $companyId)
            ->selectRaw('platform, status, COUNT(*) as total')
            ->groupBy('platform', 'status')
            ->get();
    }

    /**
     * @return Collection<int, Application>
     */
    private function applications(int $companyId, int $limit): Collection
    {
        return Application::query()
            ->where('company_id', $companyId)
            ->withCount(['environments', 'versions', 'releases'])
            ->addSelect([
                'open_issues_count' => SupportTicket::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('support_tickets.application_id', 'applications.id')
                    ->where('company_id', $companyId)
                    ->whereIn('status', $this->openTicketStatuses()),
            ])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, int|string>  $applicationIds
     * @return Collection<int, ApplicationEnvironment>
     */
    private function environments(Collection $applicationIds, int $limit): Collection
    {
        if ($applicationIds->isEmpty()) {
            return collect();
        }

        return ApplicationEnvironment::query()
            ->whereIn('application_id', $applicationIds)
            ->with('application:id,uuid,name,platform')
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, int|string>  $applicationIds
     * @return Collection<int, ApplicationVersion>
     */
    private function versions(Collection $applicationIds, int $limit): Collection
    {
        if ($applicationIds->isEmpty()) {
            return collect();
        }

        return ApplicationVersion::query()
            ->whereIn('application_id', $applicationIds)
            ->with('application:id,uuid,name')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, int|string>  $applicationIds
     * @return Collection<int, ApplicationRelease>
     */
    private function releases(Collection $applicationIds, int $limit): Collection
    {
        if ($applicationIds->isEmpty()) {
            return collect();
        }

        return ApplicationRelease::query()
            ->whereIn('application_id', $applicationIds)
            ->with([
                'application:id,uuid,name',
                'environment:id,uuid,name,type',
                'version:id,uuid,version_number',
            ])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, SupportTicket>
     */
    private function tickets(int $companyId, int $limit): Collection
    {
        return SupportTicket::query()
            ->where('company_id', $companyId)
            ->whereIn('status', $this->openTicketStatuses())
            ->with('application:id,uuid,name')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Integration>
     */
    private function integrations(int $companyId, int $limit): Collection
    {
        return Integration::query()
            ->where('company_id', $companyId)
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Notification>
     */
    private function notifications(int $companyId, int $limit): Collection
    {
        return Notification::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function activeApplicationStatus(): string
    {
        return ApplicationStatus::Active->value;
    }

    public function productionVersionStatus(): string
    {
        return ApplicationVersionStatus::Production->value;
    }

    public function deployedReleaseStatus(): string
    {
        return ApplicationReleaseStatus::Deployed->value;
    }

    public function failedNotificationStatus(): string
    {
        return NotificationStatus::Failed->value;
    }
}
