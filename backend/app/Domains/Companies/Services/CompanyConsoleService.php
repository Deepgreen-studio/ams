<?php

namespace App\Domains\Companies\Services;

use App\Domains\Applications\Enums\ApplicationEnvironmentHealthStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentType;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVersionStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Models\ApplicationVersion;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Repositories\CompanyConsoleRepository;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Collection;

class CompanyConsoleService
{
    public function __construct(
        private readonly CompanyConsoleRepository $companyConsoleRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function console(Company $company, User $actor): array
    {
        $snapshot = $this->companyConsoleRepository->snapshot($company->id);

        $applicationsByStatus = $snapshot['applications_by_status'];
        $applicationTotal = (int) $applicationsByStatus->sum();
        $activeApplications = (int) ($applicationsByStatus[$this->companyConsoleRepository->activeApplicationStatus()] ?? 0);

        $environmentTotal = (int) $snapshot['environments_by_status']->sum();
        $unhealthy = (int) ($snapshot['environments_by_health'][ApplicationEnvironmentHealthStatus::Unhealthy->value] ?? 0)
            + (int) ($snapshot['environments_by_health'][ApplicationEnvironmentHealthStatus::Degraded->value] ?? 0);

        $versionTotal = (int) $snapshot['versions_by_status']->sum();
        $productionVersions = (int) ($snapshot['versions_by_status'][$this->companyConsoleRepository->productionVersionStatus()] ?? 0);

        $releaseTotal = (int) $snapshot['releases_by_status']->sum();
        $deployedReleases = (int) ($snapshot['releases_by_status'][$this->companyConsoleRepository->deployedReleaseStatus()] ?? 0);
        $activeReleases = (int) ($snapshot['releases_by_status'][ApplicationReleaseStatus::Deploying->value] ?? 0)
            + (int) ($snapshot['releases_by_status'][ApplicationReleaseStatus::PendingApproval->value] ?? 0)
            + (int) ($snapshot['releases_by_status'][ApplicationReleaseStatus::Scheduled->value] ?? 0);

        $integrationTotal = (int) $snapshot['integrations_by_status']->sum();
        $healthyIntegrations = (int) ($snapshot['integrations_by_health'][IntegrationHealthStatus::Healthy->value] ?? 0);

        $notificationTotal = (int) $snapshot['notifications_by_status']->sum();
        $failedNotifications = (int) ($snapshot['notifications_by_status'][$this->companyConsoleRepository->failedNotificationStatus()] ?? 0);

        $pendingReleases = (int) $snapshot['pending_releases'];
        $payload = [
            'company' => [
                'uuid' => $company->uuid,
                'name' => $company->company_name,
                'status' => $company->status?->value ?? (string) $company->status,
            ],
            'profile' => $this->profile($company),
            'timezone' => $company->timezone ?: 'Asia/Kolkata',
            'kpis' => [
                $this->kpi('applications', 'Total / active applications', $applicationTotal, $activeApplications.' active', 'applications.view'),
                $this->kpi('support', 'Issues / tickets', (int) $snapshot['open_issues'], $snapshot['elevated_issues'].' high priority', 'support.view'),
                $this->kpi('releases', 'Pending releases', $pendingReleases, $deployedReleases.' deployed · '.$releaseTotal.' total', 'applications.view'),
                $this->kpi('integrations', 'Integrations', $integrationTotal, $healthyIntegrations.' healthy', 'integrations.view'),
                $this->kpi('users', 'Active users', (int) $snapshot['active_users'], 'Company membership', 'users.view'),
                $this->kpi('compliance', 'Compliance / GDPR cases', (int) $snapshot['compliance_cases'], 'Open privacy and breach cases', 'compliance.view'),
            ],
            'platforms' => $this->platforms($snapshot['applications_by_platform']),
            'applications' => $snapshot['applications']->map(fn (Application $application): array => [
                'uuid' => $application->uuid,
                'name' => $application->name,
                'platform' => $this->value($application->platform),
                'platform_label' => $this->label($application->platform),
                'status' => $this->value($application->status),
                'status_label' => $this->label($application->status),
                'current_version' => $application->current_version,
                'operational_status' => $this->operationalStatus($application),
                'environments_count' => (int) $application->environments_count,
                'versions_count' => (int) $application->versions_count,
                'releases_count' => (int) $application->releases_count,
                'open_issues_count' => (int) $application->open_issues_count,
            ])->values()->all(),
            'environments' => $snapshot['environments']->map(fn (ApplicationEnvironment $environment): array => [
                'uuid' => $environment->uuid,
                'name' => $environment->name,
                'type' => $this->value($environment->type),
                'type_label' => $this->label($environment->type),
                'status' => $this->value($environment->status),
                'status_label' => $this->label($environment->status),
                'health_status' => $this->value($environment->health_status),
                'health_label' => $this->label($environment->health_status),
                'last_health_check' => $environment->last_health_check,
                'application_uuid' => $environment->application?->uuid,
                'application_name' => $environment->application?->name,
                'platform' => $this->value($environment->application?->platform),
                'platform_label' => $this->label($environment->application?->platform),
            ])->values()->all(),
            'versions' => $snapshot['versions']->map(fn (ApplicationVersion $version): array => [
                'uuid' => $version->uuid,
                'version_number' => $version->version_number,
                'build_number' => $version->build_number,
                'status' => $this->value($version->status),
                'status_label' => $this->label($version->status),
                'release_date' => $version->release_date,
                'application_uuid' => $version->application?->uuid,
                'application_name' => $version->application?->name,
            ])->values()->all(),
            'releases' => $snapshot['releases']->map(fn (ApplicationRelease $release): array => [
                'uuid' => $release->uuid,
                'name' => $release->name,
                'version_label' => $release->version_label,
                'status' => $this->value($release->status),
                'status_label' => $this->label($release->status),
                'deployed_at' => $release->deployed_at,
                'application_uuid' => $release->application?->uuid,
                'application_name' => $release->application?->name,
                'environment_name' => $release->environment?->name,
                'environment_type' => $this->value($release->environment?->type),
            ])->values()->all(),
            'support_issues' => $snapshot['tickets']->map(fn (SupportTicket $ticket): array => [
                'uuid' => $ticket->uuid,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $this->value($ticket->status),
                'status_label' => $this->label($ticket->status),
                'priority' => $this->value($ticket->priority),
                'priority_label' => $this->label($ticket->priority),
                'application_name' => $ticket->application?->name,
                'created_at' => $ticket->created_at,
            ])->values()->all(),
            'integrations' => $snapshot['integrations']->map(fn (Integration $integration): array => [
                'uuid' => $integration->uuid,
                'name' => $integration->name,
                'type' => $this->value($integration->type),
                'type_label' => $this->label($integration->type),
                'status' => $this->value($integration->status),
                'status_label' => $this->label($integration->status),
                'health_status' => $this->value($integration->health_status),
                'health_label' => $this->label($integration->health_status),
                'last_health_check' => $integration->last_health_check,
            ])->values()->all(),
            'notifications' => $snapshot['notifications']->map(fn (Notification $notification): array => [
                'uuid' => $notification->uuid,
                'title' => $notification->title,
                'channel' => $this->value($notification->channel),
                'status' => $this->value($notification->status),
                'status_label' => $this->label($notification->status),
                'priority' => $this->value($notification->priority),
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ])->values()->all(),
            'organization' => $this->organization($snapshot['organization']),
            'activity' => $snapshot['activity']->map(fn ($entry): array => [
                'id' => $entry->id,
                'user' => $entry->causer?->full_name ?: 'System',
                'action' => $entry->description,
                'event' => $entry->event,
                'entity' => class_basename((string) $entry->subject_type),
                'created_at' => $entry->created_at,
            ])->values()->all(),
            'summary' => [
                'environments' => $environmentTotal,
                'unhealthy_environments' => $unhealthy,
                'versions' => $versionTotal,
                'production_versions' => $productionVersions,
                'notifications' => $notificationTotal,
                'failed_notifications' => $failedNotifications,
            ],
        ];

        return $this->restrict($payload, $actor);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function restrict(array $payload, User $actor): array
    {
        $sections = [
            ['key' => 'overview', 'label' => 'Overview', 'permission' => 'companies.view'],
        ];

        $map = [
            'applications' => ['applications', 'platforms', 'environments', 'versions', 'releases'],
            'users' => ['organization'],
            'integrations' => ['integrations'],
            'support' => ['support_issues'],
            'compliance' => [],
            'settings' => [],
            'activity' => ['activity'],
        ];

        $allowed = [
            'applications' => $actor->can('applications.view'),
            'users' => $actor->can('users.view'),
            'integrations' => $actor->can('integrations.view'),
            'support' => $actor->can('support.view'),
            'compliance' => $actor->can('compliance.view'),
            'settings' => $actor->can('companies.update') || $actor->can('companies.manage'),
            'activity' => $actor->can('audit.view'),
        ];

        $labels = [
            'applications' => 'Applications',
            'users' => 'Users & Teams',
            'integrations' => 'Integrations',
            'support' => 'Support',
            'compliance' => 'Compliance',
            'settings' => 'Settings',
            'activity' => 'Activity',
        ];

        foreach ($allowed as $key => $visible) {
            if ($visible) {
                $sections[] = ['key' => $key, 'label' => $labels[$key]];
                continue;
            }

            foreach ($map[$key] as $field) {
                unset($payload[$field]);
            }
        }

        if (! $allowed['applications']) {
            unset($payload['applications'], $payload['platforms'], $payload['environments'], $payload['versions'], $payload['releases']);
        }

        $payload['kpis'] = array_values(array_filter(
            $payload['kpis'],
            fn (array $kpi): bool => $actor->can($kpi['permission']),
        ));
        $payload['sections'] = $sections;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(Company $company): array
    {
        $settings = is_array($company->settings) ? $company->settings : [];
        $address = collect([
            $company->address,
            $company->city,
            $company->state,
            $company->postal_code,
            $company->country,
        ])->filter()->implode(', ');

        return [
            'display_name' => $company->company_name,
            'legal_name' => $company->legal_name,
            'company_code' => $company->registration_number,
            'address' => $address !== '' ? $address : null,
            'website' => $company->website,
            'primary_contact' => $company->email,
            'support_contact' => $settings['support_phone'] ?? $company->phone,
            'support_email' => $settings['support_email'] ?? null,
            'logo_url' => $company->logo_url,
            'favicon_url' => $company->favicon_url,
            'primary_color' => $company->primary_color,
            'secondary_color' => $company->secondary_color,
            'timezone' => $company->timezone ?: 'Asia/Kolkata',
            'currency' => $company->currency,
        ];
    }

    private function operationalStatus(Application $application): string
    {
        $open = (int) $application->open_issues_count;
        if ($open > 0) {
            return $open.' open issues';
        }

        $status = $this->label($application->status);

        return $status.' · '.$application->environments_count.' environments';
    }

    /**
     * @param  array<string, mixed>  $organization
     * @return array<string, mixed>
     */
    private function organization(array $organization): array
    {
        $users = $organization['users']->map(fn (User $user): array => [
            'uuid' => $user->uuid,
            'name' => $user->full_name,
            'email' => $user->email,
            'status' => $this->value($user->status),
            'department' => $user->department?->name,
            'team' => $user->team?->name,
            'location' => $user->location?->branch_name,
        ])->values()->all();

        return [
            'departments' => $organization['departments']->map(function ($department) use ($users): array {
                return [
                    'uuid' => $department->uuid,
                    'name' => $department->name,
                    'status' => $this->value($department->status),
                    'teams' => $department->teams->map(fn ($team): array => [
                        'uuid' => $team->uuid,
                        'name' => $team->name,
                        'manager' => $team->manager?->full_name,
                        'members' => array_values(array_filter(
                            $users,
                            fn (array $user): bool => $user['team'] === $team->name || ($user['department'] === $department->name && $user['team'] === null),
                        )),
                    ])->values()->all(),
                ];
            })->values()->all(),
            'locations' => $organization['locations']->map(fn ($location): array => [
                'uuid' => $location->uuid,
                'name' => $location->branch_name,
                'city' => $location->city,
                'country' => $location->country,
                'status' => $this->value($location->status),
            ])->values()->all(),
            'users' => $users,
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function platforms(Collection $rows): array
    {
        $totals = [];
        foreach (ApplicationPlatform::cases() as $platform) {
            $totals[$platform->value] = [
                'platform' => $platform->value,
                'label' => $platform->label(),
                'total' => 0,
                'active' => 0,
            ];
        }

        foreach ($rows as $row) {
            $platform = $this->value($row->platform) ?? '';
            if (! isset($totals[$platform])) {
                $totals[$platform] = [
                    'platform' => $platform,
                    'label' => $this->label($platform),
                    'total' => 0,
                    'active' => 0,
                ];
            }

            $count = (int) $row->total;
            $totals[$platform]['total'] += $count;
            if ($this->value($row->status) === ApplicationStatus::Active->value) {
                $totals[$platform]['active'] += $count;
            }
        }

        return array_values($totals);
    }

    /**
     * @return array<string, mixed>
     */
    private function kpi(string $key, string $label, int $value, string $detail, string $permission): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'detail' => $detail,
            'permission' => $permission,
        ];
    }

    private function value(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    private function label(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_object($value) && method_exists($value, 'label')) {
            return $value->label();
        }

        $raw = $value instanceof BackedEnum ? (string) $value->value : (string) $value;

        foreach ([
            ApplicationStatus::class,
            ApplicationPlatform::class,
            ApplicationEnvironmentStatus::class,
            ApplicationEnvironmentHealthStatus::class,
            ApplicationEnvironmentType::class,
            ApplicationVersionStatus::class,
            ApplicationReleaseStatus::class,
            SupportTicketStatus::class,
            SupportTicketPriority::class,
            IntegrationStatus::class,
            IntegrationHealthStatus::class,
            NotificationStatus::class,
        ] as $enum) {
            $case = $enum::tryFrom($raw);
            if ($case && method_exists($case, 'label')) {
                return $case->label();
            }
        }

        return ucfirst(str_replace('_', ' ', $raw));
    }
}
