<?php

namespace App\Domains\Applications\Listeners;

use App\Domains\Applications\Events\ApplicationAnalyticsIngested;
use App\Domains\Applications\Events\ApplicationConfigurationCreated;
use App\Domains\Applications\Events\ApplicationConfigurationDeleted;
use App\Domains\Applications\Events\ApplicationConfigurationRestoredHistory;
use App\Domains\Applications\Events\ApplicationConfigurationUpdated;
use App\Domains\Applications\Events\ApplicationCrashReported;
use App\Domains\Applications\Events\ApplicationCrashUpdated;
use App\Domains\Applications\Events\ApplicationCreated;
use App\Domains\Applications\Events\ApplicationDeleted;
use App\Domains\Applications\Events\ApplicationEnvironmentCreated;
use App\Domains\Applications\Events\ApplicationEnvironmentDeleted;
use App\Domains\Applications\Events\ApplicationEnvironmentHealthChecked;
use App\Domains\Applications\Events\ApplicationEnvironmentSwitched;
use App\Domains\Applications\Events\ApplicationEnvironmentUpdated;
use App\Domains\Applications\Events\ApplicationHealthMetricRecorded;
use App\Domains\Applications\Events\ApplicationMonitoringAlertTriggered;
use App\Domains\Applications\Events\ApplicationReleaseApproved;
use App\Domains\Applications\Events\ApplicationReleaseCreated;
use App\Domains\Applications\Events\ApplicationReleaseDeleted;
use App\Domains\Applications\Events\ApplicationReleaseDeployed;
use App\Domains\Applications\Events\ApplicationReleaseRejected;
use App\Domains\Applications\Events\ApplicationReleaseRolledBack;
use App\Domains\Applications\Events\ApplicationReleaseUpdated;
use App\Domains\Applications\Events\ApplicationRestored;
use App\Domains\Applications\Events\ApplicationUpdated;
use App\Domains\Applications\Events\ApplicationVersionCreated;
use App\Domains\Applications\Events\ApplicationVersionDeleted;
use App\Domains\Applications\Events\ApplicationVersionUpdated;

class LogApplicationActivity
{
    public function handleApplicationCreated(ApplicationCreated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->application)
            ->withProperties([
                'event' => 'application_created',
                'name' => $event->application->name,
                'platform' => $event->application->platform?->value ?? $event->application->platform,
                'status' => $event->application->status?->value ?? $event->application->status,
            ])
            ->log('Application created');
    }

    public function handleApplicationUpdated(ApplicationUpdated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->application)
            ->withProperties([
                'event' => 'application_updated',
                'name' => $event->application->name,
                'status' => $event->application->status?->value ?? $event->application->status,
                'visibility' => $event->application->visibility?->value ?? $event->application->visibility,
            ])
            ->log('Application updated');
    }

    public function handleApplicationDeleted(ApplicationDeleted $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->application)
            ->withProperties([
                'event' => 'application_deleted',
                'name' => $event->application->name,
            ])
            ->log('Application deleted');
    }

    public function handleApplicationRestored(ApplicationRestored $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->application)
            ->withProperties([
                'event' => 'application_restored',
                'name' => $event->application->name,
            ])
            ->log('Application restored');
    }

    public function handleApplicationVersionCreated(ApplicationVersionCreated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->version)
            ->withProperties([
                'event' => 'application_version_created',
                'version_number' => $event->version->version_number,
                'status' => $event->version->status?->value ?? $event->version->status,
                'application_id' => $event->version->application_id,
            ])
            ->log('Application version created');
    }

    public function handleApplicationVersionUpdated(ApplicationVersionUpdated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->version)
            ->withProperties([
                'event' => 'application_version_updated',
                'version_number' => $event->version->version_number,
                'status' => $event->version->status?->value ?? $event->version->status,
                'application_id' => $event->version->application_id,
            ])
            ->log('Application version updated');
    }

    public function handleApplicationVersionDeleted(ApplicationVersionDeleted $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->version)
            ->withProperties([
                'event' => 'application_version_deleted',
                'version_number' => $event->version->version_number,
                'application_id' => $event->version->application_id,
            ])
            ->log('Application version deleted');
    }

    public function handleApplicationEnvironmentCreated(ApplicationEnvironmentCreated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->environment)
            ->withProperties([
                'event' => 'application_environment_created',
                'name' => $event->environment->name,
                'type' => $event->environment->type?->value ?? $event->environment->type,
                'application_id' => $event->environment->application_id,
            ])
            ->log('Application environment created');
    }

    public function handleApplicationEnvironmentUpdated(ApplicationEnvironmentUpdated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->environment)
            ->withProperties([
                'event' => 'application_environment_updated',
                'name' => $event->environment->name,
                'status' => $event->environment->status?->value ?? $event->environment->status,
                'application_id' => $event->environment->application_id,
            ])
            ->log('Application environment updated');
    }

    public function handleApplicationEnvironmentDeleted(ApplicationEnvironmentDeleted $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->environment)
            ->withProperties([
                'event' => 'application_environment_deleted',
                'name' => $event->environment->name,
                'application_id' => $event->environment->application_id,
            ])
            ->log('Application environment deleted');
    }

    public function handleApplicationEnvironmentSwitched(ApplicationEnvironmentSwitched $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->environment)
            ->withProperties([
                'event' => 'application_environment_switched',
                'name' => $event->environment->name,
                'type' => $event->environment->type?->value ?? $event->environment->type,
                'application_id' => $event->environment->application_id,
            ])
            ->log('Application environment switched');
    }

    public function handleApplicationEnvironmentHealthChecked(ApplicationEnvironmentHealthChecked $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->environment)
            ->withProperties([
                'event' => 'application_environment_health_checked',
                'name' => $event->environment->name,
                'health_status' => $event->check['health_status'] ?? null,
                'success' => $event->check['success'] ?? null,
                'status_code' => $event->check['status_code'] ?? null,
                'latency_ms' => $event->check['latency_ms'] ?? null,
            ])
            ->log('Application environment health checked');
    }

    public function handleApplicationConfigurationCreated(ApplicationConfigurationCreated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->configuration)
            ->withProperties([
                'event' => 'application_configuration_created',
                'type' => $event->configuration->type?->value ?? $event->configuration->type,
                'name' => $event->configuration->name,
                'version' => $event->configuration->version,
            ])
            ->log('Application configuration created');
    }

    public function handleApplicationConfigurationUpdated(ApplicationConfigurationUpdated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->configuration)
            ->withProperties([
                'event' => 'application_configuration_updated',
                'type' => $event->configuration->type?->value ?? $event->configuration->type,
                'status' => $event->configuration->status?->value ?? $event->configuration->status,
                'version' => $event->configuration->version,
            ])
            ->log('Application configuration updated');
    }

    public function handleApplicationConfigurationDeleted(ApplicationConfigurationDeleted $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->configuration)
            ->withProperties([
                'event' => 'application_configuration_deleted',
                'type' => $event->configuration->type?->value ?? $event->configuration->type,
                'name' => $event->configuration->name,
            ])
            ->log('Application configuration deleted');
    }

    public function handleApplicationConfigurationRestoredHistory(ApplicationConfigurationRestoredHistory $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->configuration)
            ->withProperties([
                'event' => 'application_configuration_restored_history',
                'type' => $event->configuration->type?->value ?? $event->configuration->type,
                'restored_from_version' => $event->history->version,
                'version' => $event->configuration->version,
            ])
            ->log('Application configuration restored from history');
    }

    public function handleApplicationReleaseCreated(ApplicationReleaseCreated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_created',
                'name' => $event->release->name,
                'version_label' => $event->release->version_label,
                'status' => $event->release->status?->value ?? $event->release->status,
            ])
            ->log('Application release created');
    }

    public function handleApplicationReleaseUpdated(ApplicationReleaseUpdated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_updated',
                'name' => $event->release->name,
                'status' => $event->release->status?->value ?? $event->release->status,
                'approval_status' => $event->release->approval_status?->value ?? $event->release->approval_status,
            ])
            ->log('Application release updated');
    }

    public function handleApplicationReleaseDeleted(ApplicationReleaseDeleted $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_deleted',
                'name' => $event->release->name,
                'version_label' => $event->release->version_label,
            ])
            ->log('Application release deleted');
    }

    public function handleApplicationReleaseApproved(ApplicationReleaseApproved $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_approved',
                'name' => $event->release->name,
                'approved_by' => $event->actor->id,
            ])
            ->log('Application release approved');
    }

    public function handleApplicationReleaseRejected(ApplicationReleaseRejected $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_rejected',
                'name' => $event->release->name,
            ])
            ->log('Application release rejected');
    }

    public function handleApplicationReleaseDeployed(ApplicationReleaseDeployed $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_deployed',
                'name' => $event->release->name,
                'deployment_date' => $event->release->deployment_date,
            ])
            ->log('Application release deployed');
    }

    public function handleApplicationReleaseRolledBack(ApplicationReleaseRolledBack $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->release)
            ->withProperties([
                'event' => 'application_release_rolled_back',
                'name' => $event->release->name,
                'rollback_status' => $event->release->rollback_status?->value ?? $event->release->rollback_status,
            ])
            ->log('Application release rolled back');
    }

    public function handleApplicationCrashReported(ApplicationCrashReported $event): void
    {
        $logger = activity('applications')->performedOn($event->crash);
        if ($event->actor) {
            $logger->causedBy($event->actor);
        }
        $logger->withProperties([
            'event' => 'application_crash_reported',
            'type' => $event->crash->type?->value ?? $event->crash->type,
            'title' => $event->crash->title,
            'from_ingest' => $event->fromIngest,
            'occurrence_count' => $event->crash->occurrence_count,
        ])->log('Application crash reported');
    }

    public function handleApplicationCrashUpdated(ApplicationCrashUpdated $event): void
    {
        activity('applications')
            ->causedBy($event->actor)
            ->performedOn($event->crash)
            ->withProperties([
                'event' => 'application_crash_updated',
                'status' => $event->crash->status?->value ?? $event->crash->status,
                'title' => $event->crash->title,
            ])
            ->log('Application crash updated');
    }

    public function handleApplicationHealthMetricRecorded(ApplicationHealthMetricRecorded $event): void
    {
        $logger = activity('applications')->performedOn($event->metric);
        if ($event->actor) {
            $logger->causedBy($event->actor);
        }
        $logger->withProperties([
            'event' => 'application_health_metric_recorded',
            'health_score' => $event->metric->health_score,
            'crash_rate' => $event->metric->crash_rate,
        ])->log('Application health metric recorded');
    }

    public function handleApplicationMonitoringAlertTriggered(ApplicationMonitoringAlertTriggered $event): void
    {
        activity('applications')
            ->performedOn($event->event)
            ->withProperties([
                'event' => 'application_monitoring_alert_triggered',
                'alert' => $event->alert->name,
                'metric' => $event->event->metric,
                'observed_value' => $event->event->observed_value,
                'threshold' => $event->event->threshold,
            ])
            ->log('Application monitoring alert triggered');
    }

    public function handleApplicationAnalyticsIngested(ApplicationAnalyticsIngested $event): void
    {
        $logger = activity('applications')->performedOn($event->daily);
        if ($event->actor) {
            $logger->causedBy($event->actor);
        }
        $logger->withProperties([
            'event' => 'application_analytics_ingested',
            'metric_date' => optional($event->daily->metric_date)->toDateString(),
            'daily_users' => $event->daily->daily_users,
            'monthly_users' => $event->daily->monthly_users,
            'installs' => $event->daily->installs,
        ])->log('Application analytics ingested');
    }
}
