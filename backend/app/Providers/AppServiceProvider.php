<?php

namespace App\Providers;

use App\Domains\Audit\Events\ActivityLogged;
use App\Domains\Audit\Events\ApiLogged;
use App\Domains\Audit\Events\AuditCreated;
use App\Domains\Audit\Events\ErrorCaptured;
use App\Domains\Audit\Events\SystemEventCreated;
use App\Domains\Audit\Listeners\PrepareAuditNotifications;
use App\Domains\Audit\Listeners\RecordLoginHistory;
use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Models\ApiLog;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Models\ErrorLog;
use App\Domains\Audit\Models\SystemEvent;
use App\Domains\Audit\Policies\AuditPolicy;
use App\Domains\Users\Models\UserLoginHistory;
use App\Domains\Settings\Events\ConfigurationChanged;
use App\Domains\Settings\Events\FolderCreated;
use App\Domains\Settings\Events\FolderDeleted;
use App\Domains\Settings\Events\MediaDeleted;
use App\Domains\Settings\Events\MediaUploaded;
use App\Domains\Settings\Events\SettingsUpdated;
use App\Domains\Settings\Listeners\LogSettingsActivity;
use App\Domains\Settings\Listeners\PrepareSettingsNotifications;
use App\Domains\Settings\Models\FileFolder;
use App\Domains\Settings\Models\MediaFile;
use App\Domains\Settings\Models\SystemSetting;
use App\Domains\Settings\Policies\SettingsPolicy;
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
use App\Domains\Applications\Listeners\LogApplicationActivity;
use App\Domains\Applications\Listeners\PrepareApplicationNotifications;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Policies\ApplicationPolicy;
use App\Domains\Content\Events\ContentCreated;
use App\Domains\Content\Events\ContentDeleted;
use App\Domains\Content\Events\ContentPublished;
use App\Domains\Content\Events\ContentRestored;
use App\Domains\Content\Events\ContentUnpublished;
use App\Domains\Content\Events\ContentUpdated;
use App\Domains\Content\Events\ContentVersionRestored;
use App\Domains\Content\Events\ContentWorkflowTransitioned;
use App\Domains\Content\Events\MediaLibraryDeleted;
use App\Domains\Content\Events\MediaLibraryReplaced;
use App\Domains\Content\Events\MediaLibraryUploaded;
use App\Domains\Content\Listeners\LogContentActivity;
use App\Domains\Content\Listeners\PrepareContentNotifications;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Policies\ContentPolicy;
use App\Domains\Companies\Events\BrandingUpdated;
use App\Domains\Companies\Events\CompanyCreated;
use App\Domains\Companies\Events\CompanyDeleted;
use App\Domains\Companies\Events\CompanyUpdated;
use App\Domains\Companies\Events\DepartmentCreated;
use App\Domains\Companies\Events\DepartmentUpdated;
use App\Domains\Companies\Events\LocationCreated;
use App\Domains\Companies\Events\TeamCreated;
use App\Domains\Companies\Listeners\LogCompanyActivity;
use App\Domains\Companies\Listeners\PrepareCompanyNotifications;
use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\CompanyLocation;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Companies\Policies\CompanyPolicy;
use App\Domains\Customers\Contracts\SubscriptionBillingGatewayInterface;
use App\Domains\Customers\Events\CustomerAnalyticsSnapshotComputed;
use App\Domains\Customers\Events\CustomerApplicationAssigned;
use App\Domains\Customers\Events\CustomerApplicationDeleted;
use App\Domains\Customers\Events\CustomerApplicationRestored;
use App\Domains\Customers\Events\CustomerApplicationUpdated;
use App\Domains\Customers\Events\CustomerContactCreated;
use App\Domains\Customers\Events\CustomerContactDeleted;
use App\Domains\Customers\Events\CustomerContactRestored;
use App\Domains\Customers\Events\CustomerContactUpdated;
use App\Domains\Customers\Events\CustomerCreated;
use App\Domains\Customers\Events\CustomerDeleted;
use App\Domains\Customers\Events\CustomerDocumentDeleted;
use App\Domains\Customers\Events\CustomerDocumentRestored;
use App\Domains\Customers\Events\CustomerDocumentUpdated;
use App\Domains\Customers\Events\CustomerDocumentUploaded;
use App\Domains\Customers\Events\CustomerDocumentVersionUploaded;
use App\Domains\Customers\Events\CustomerNoteCreated;
use App\Domains\Customers\Events\CustomerNoteDeleted;
use App\Domains\Customers\Events\CustomerNoteRestored;
use App\Domains\Customers\Events\CustomerNoteUpdated;
use App\Domains\Customers\Events\CustomerCommunicationCreated;
use App\Domains\Customers\Events\CustomerCommunicationDeleted;
use App\Domains\Customers\Events\CustomerCommunicationRestored;
use App\Domains\Customers\Events\CustomerCommunicationUpdated;
use App\Domains\Customers\Events\CustomerRestored;
use App\Domains\Customers\Events\CustomerTaskCompleted;
use App\Domains\Customers\Events\CustomerTaskCreated;
use App\Domains\Customers\Events\CustomerTaskDeleted;
use App\Domains\Customers\Events\CustomerTaskRestored;
use App\Domains\Customers\Events\CustomerTaskUpdated;
use App\Domains\Customers\Events\CustomerUpdated;
use App\Domains\Customers\Events\LicenseCreated;
use App\Domains\Customers\Events\LicenseDeleted;
use App\Domains\Customers\Events\LicenseRestored;
use App\Domains\Customers\Events\LicenseRevoked;
use App\Domains\Customers\Events\LicenseUpdated;
use App\Domains\Customers\Events\SubscriptionCancelled;
use App\Domains\Customers\Events\SubscriptionCreated;
use App\Domains\Customers\Events\SubscriptionDeleted;
use App\Domains\Customers\Events\SubscriptionRestored;
use App\Domains\Customers\Events\SubscriptionUpdated;
use App\Domains\Customers\Listeners\LogCustomerActivity;
use App\Domains\Customers\Listeners\LogCustomerAnalyticsActivity;
use App\Domains\Customers\Listeners\LogCustomerApplicationActivity;
use App\Domains\Customers\Listeners\LogCustomerCommunicationCenterActivity;
use App\Domains\Customers\Listeners\LogCustomerContactActivity;
use App\Domains\Customers\Listeners\LogCustomerDocumentActivity;
use App\Domains\Customers\Listeners\LogLicenseActivity;
use App\Domains\Customers\Listeners\LogSubscriptionActivity;
use App\Domains\Customers\Listeners\PrepareCustomerApplicationNotifications;
use App\Domains\Customers\Listeners\PrepareCustomerCommunicationCenterNotifications;
use App\Domains\Customers\Listeners\PrepareCustomerContactNotifications;
use App\Domains\Customers\Listeners\PrepareCustomerDocumentNotifications;
use App\Domains\Customers\Listeners\PrepareCustomerNotifications;
use App\Domains\Customers\Listeners\PrepareLicenseNotifications;
use App\Domains\Customers\Listeners\PrepareSubscriptionNotifications;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Customers\Models\CustomerCommunication;
use App\Domains\Customers\Models\CustomerContact;
use App\Domains\Customers\Models\CustomerDocument;
use App\Domains\Customers\Models\CustomerNote;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Customers\Models\License;
use App\Domains\Customers\Models\Subscription;
use App\Domains\Customers\Policies\CustomerPolicy;
use App\Domains\Customers\Services\Billing\ManualBillingGateway;
use App\Domains\Customers\Services\Billing\StripeBillingGateway;
use App\Domains\Monitoring\Models\MonitoringAlert;
use App\Domains\Monitoring\Models\MonitoringLog;
use App\Domains\Monitoring\Models\MonitoringSnapshot;
use App\Domains\Monitoring\Models\HealthCheck;
use App\Domains\Monitoring\Models\ServiceStatus;
use App\Domains\Monitoring\Policies\MonitoringPolicy;
use App\Domains\Queue\Models\QueueJobTrack;
use App\Domains\Queue\Policies\QueuePolicy;
use App\Domains\Integrations\Events\DataMappingCreated;
use App\Domains\Integrations\Events\DataMappingDeleted;
use App\Domains\Integrations\Events\DataMappingUpdated;
use App\Domains\Integrations\Events\IntegrationConfigurationUpdated;
use App\Domains\Integrations\Events\IntegrationConnectionExecuted;
use App\Domains\Integrations\Events\IntegrationCreated;
use App\Domains\Integrations\Events\IntegrationDeleted;
use App\Domains\Integrations\Events\IntegrationRestored;
use App\Domains\Integrations\Events\IntegrationUpdated;
use App\Domains\Integrations\Events\SyncRunCompleted;
use App\Domains\Integrations\Events\SyncRunFailed;
use App\Domains\Integrations\Events\SyncRunStarted;
use App\Domains\Integrations\Events\WebhookCreated;
use App\Domains\Integrations\Events\WebhookDeleted;
use App\Domains\Integrations\Events\WebhookDelivered;
use App\Domains\Integrations\Events\WebhookFailed;
use App\Domains\Integrations\Events\WebhookUpdated;
use App\Domains\Integrations\Listeners\LogIntegrationActivity;
use App\Domains\Integrations\Listeners\PrepareIntegrationNotifications;
use App\Domains\Integrations\Models\DataMapping;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Models\SyncRun;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Domains\Integrations\Policies\DataMappingPolicy;
use App\Domains\Integrations\Policies\IntegrationPolicy;
use App\Domains\Integrations\Policies\SyncPolicy;
use App\Domains\Integrations\Policies\WebhookPolicy;
use App\Domains\Authentication\Events\EmailVerified;
use App\Domains\Authentication\Events\PasswordChanged;
use App\Domains\Authentication\Events\PasswordResetCompleted;
use App\Domains\Authentication\Events\PasswordResetRequested;
use App\Domains\Authentication\Events\UserLoggedIn;
use App\Domains\Authentication\Events\UserLoggedOut;
use App\Domains\Authentication\Listeners\LogAuthenticationActivity;
use App\Domains\Compliance\Events\ComplianceCaseAssigned;
use App\Domains\Compliance\Events\ComplianceCaseCreated;
use App\Domains\Compliance\Events\ComplianceCaseDeleted;
use App\Domains\Compliance\Events\ComplianceCaseRestored;
use App\Domains\Compliance\Events\ComplianceCaseUpdated;
use App\Domains\Compliance\Events\ConsentGranted;
use App\Domains\Compliance\Events\ConsentUpdated;
use App\Domains\Compliance\Events\ConsentWithdrawn;
use App\Domains\Compliance\Events\DataBreachActionRecorded;
use App\Domains\Compliance\Events\DataBreachAssigned;
use App\Domains\Compliance\Events\DataBreachClosed;
use App\Domains\Compliance\Events\DataBreachContained;
use App\Domains\Compliance\Events\DataBreachCreated;
use App\Domains\Compliance\Events\DataBreachDeleted;
use App\Domains\Compliance\Events\DataBreachNotificationSent;
use App\Domains\Compliance\Events\DataBreachRecovered;
use App\Domains\Compliance\Events\DataBreachRestored;
use App\Domains\Compliance\Events\DataBreachRiskAssessed;
use App\Domains\Compliance\Events\DataBreachStatusChanged;
use App\Domains\Compliance\Events\DataBreachUpdated;
use App\Domains\Compliance\Events\DpiaApproved;
use App\Domains\Compliance\Events\DpiaCreated;
use App\Domains\Compliance\Events\DpiaRejected;
use App\Domains\Compliance\Events\DpiaSubmitted;
use App\Domains\Compliance\Events\DpiaUpdated;
use App\Domains\Compliance\Events\PolicyApproved;
use App\Domains\Compliance\Events\PolicyCreated;
use App\Domains\Compliance\Events\PolicyPublished;
use App\Domains\Compliance\Events\PolicyRejected;
use App\Domains\Compliance\Events\PolicySubmittedForReview;
use App\Domains\Compliance\Events\PolicyUpdated;
use App\Domains\Compliance\Events\PolicyVersionRestored;
use App\Domains\Compliance\Events\PrivacyRequestApproved;
use App\Domains\Compliance\Events\PrivacyRequestAssigned;
use App\Domains\Compliance\Events\PrivacyRequestCompleted;
use App\Domains\Compliance\Events\PrivacyRequestCreated;
use App\Domains\Compliance\Events\PrivacyRequestDataDeleted;
use App\Domains\Compliance\Events\PrivacyRequestExportGenerated;
use App\Domains\Compliance\Events\PrivacyRequestIdentityVerified;
use App\Domains\Compliance\Events\PrivacyRequestRejected;
use App\Domains\Compliance\Events\PrivacyRequestStatusChanged;
use App\Domains\Compliance\Events\PrivacyRequestUpdated;
use App\Domains\Compliance\Events\RiskActionRecorded;
use App\Domains\Compliance\Events\RiskCreated;
use App\Domains\Compliance\Events\RiskUpdated;
use App\Domains\Compliance\Listeners\LogComplianceActivity;
use App\Domains\Compliance\Listeners\LogConsentActivity;
use App\Domains\Compliance\Listeners\LogDataBreachActivity;
use App\Domains\Compliance\Listeners\LogDpiaActivity;
use App\Domains\Compliance\Listeners\LogPolicyDocumentActivity;
use App\Domains\Compliance\Listeners\LogPrivacyRequestActivity;
use App\Domains\Compliance\Listeners\PrepareComplianceNotifications;
use App\Domains\Compliance\Listeners\PrepareConsentNotifications;
use App\Domains\Compliance\Listeners\PrepareDataBreachNotifications;
use App\Domains\Compliance\Listeners\PrepareDpiaNotifications;
use App\Domains\Compliance\Listeners\PreparePolicyDocumentNotifications;
use App\Domains\Compliance\Listeners\PreparePrivacyRequestNotifications;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Models\RiskRegister;
use App\Domains\Compliance\Models\UserConsent;
use App\Domains\Compliance\Policies\ComplianceCasePolicy;
use App\Domains\Compliance\Policies\ConsentPolicy;
use App\Domains\Compliance\Policies\DataBreachPolicy;
use App\Domains\Compliance\Policies\DpiaPolicy;
use App\Domains\Compliance\Policies\PolicyDocumentPolicy;
use App\Domains\Compliance\Policies\PrivacyRequestPolicy;
use App\Domains\Support\Events\SupportTicketAssigned;
use App\Domains\Support\Events\SupportTicketAttachmentUploaded;
use App\Domains\Support\Events\SupportTicketClosed;
use App\Domains\Support\Events\SupportTicketCreated;
use App\Domains\Support\Events\SupportTicketDeleted;
use App\Domains\Support\Events\SupportTicketMessageCreated;
use App\Domains\Support\Events\SupportTicketReopened;
use App\Domains\Support\Events\SupportTicketRestored;
use App\Domains\Support\Events\SupportTicketSlaBreached;
use App\Domains\Support\Events\SupportTicketSlaEscalated;
use App\Domains\Support\Events\SupportTicketSlaWarning;
use App\Domains\Support\Events\SupportTicketStatusChanged;
use App\Domains\Support\Events\SupportTicketUpdated;
use App\Domains\Support\Listeners\LogSupportActivity;
use App\Domains\Support\Listeners\PrepareSupportNotifications;
use App\Domains\Support\Listeners\RoutePersonalDataTicketToCompliance;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Policies\SupportTicketPolicy;
use App\Domains\Notifications\Events\NotificationChannelUpdated;
use App\Domains\Notifications\Events\NotificationCreated;
use App\Domains\Notifications\Events\NotificationDeleted;
use App\Domains\Notifications\Events\NotificationPreferencesUpdated;
use App\Domains\Notifications\Events\NotificationRead;
use App\Domains\Notifications\Events\NotificationTemplateApproved;
use App\Domains\Notifications\Events\NotificationTemplateCreated;
use App\Domains\Notifications\Events\NotificationTemplateDeleted;
use App\Domains\Notifications\Events\NotificationTemplatePublished;
use App\Domains\Notifications\Events\NotificationTemplateRejected;
use App\Domains\Notifications\Events\NotificationTemplateSubmitted;
use App\Domains\Notifications\Events\NotificationTemplateUpdated;
use App\Domains\Notifications\Listeners\LogNotificationActivity;
use App\Domains\Notifications\Models\Notification as PlatformNotification;
use App\Domains\Notifications\Models\NotificationChannel;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Policies\NotificationChannelPolicy;
use App\Domains\Notifications\Policies\NotificationPolicy;
use App\Domains\Notifications\Policies\NotificationTemplatePolicy;
use App\Domains\Automation\Events\AutomationRuleCreated;
use App\Domains\Automation\Events\AutomationRuleDeleted;
use App\Domains\Automation\Events\AutomationRuleUpdated;
use App\Domains\Automation\Listeners\LogAutomationActivity;
use App\Domains\Automation\Listeners\RunAutomationRules;
use App\Domains\Automation\Models\AutomationRule;
use App\Domains\Automation\Policies\AutomationRulePolicy;
use App\Domains\Workflows\Events\WorkflowCreated;
use App\Domains\Workflows\Events\WorkflowDeleted;
use App\Domains\Workflows\Events\WorkflowUpdated;
use App\Domains\Workflows\Listeners\LogWorkflowActivity;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Policies\WorkflowPolicy;
use App\Domains\Scheduler\Events\ScheduledJobCreated;
use App\Domains\Scheduler\Events\ScheduledJobDeleted;
use App\Domains\Scheduler\Events\ScheduledJobUpdated;
use App\Domains\Scheduler\Listeners\LogSchedulerActivity;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Policies\ScheduledJobPolicy;
use App\Domains\Ai\Events\AiProviderCreated;
use App\Domains\Ai\Events\AiProviderDeleted;
use App\Domains\Ai\Events\AiProviderUpdated;
use App\Domains\Ai\Events\AiPromptCreated;
use App\Domains\Ai\Events\AiPromptDeleted;
use App\Domains\Ai\Events\AiPromptUpdated;
use App\Domains\Ai\Listeners\LogAiActivity;
use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Policies\AiProviderPolicy;
use App\Domains\Analytics\Events\AnalyticsDashboardCreated;
use App\Domains\Analytics\Events\AnalyticsDashboardDeleted;
use App\Domains\Analytics\Events\AnalyticsDashboardUpdated;
use App\Domains\Analytics\Events\AnalyticsEventRecorded;
use App\Domains\Analytics\Events\AnalyticsWidgetCreated;
use App\Domains\Analytics\Events\AnalyticsWidgetDeleted;
use App\Domains\Analytics\Events\AnalyticsWidgetUpdated;
use App\Domains\Analytics\Listeners\LogAnalyticsActivity;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Models\AnalyticsReportRun;
use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Models\AnalyticsWidget;
use App\Domains\Analytics\Policies\AnalyticsPolicy;
use App\Domains\Roles\Events\PermissionAssigned;
use App\Domains\Roles\Events\PermissionRemoved;
use App\Domains\Roles\Events\RoleCreated;
use App\Domains\Roles\Events\RoleDeleted;
use App\Domains\Roles\Events\RoleUpdated;
use App\Domains\Roles\Events\UserRoleAssigned;
use App\Domains\Roles\Events\UserRoleRemoved;
use App\Domains\Roles\Listeners\LogRoleActivity;
use App\Domains\Roles\Listeners\PrepareRoleNotifications;
use App\Domains\Roles\Models\Permission;
use App\Domains\Roles\Models\Role;
use App\Domains\Roles\Policies\PermissionPolicy;
use App\Domains\Roles\Policies\RolePolicy;
use App\Domains\Users\Events\AvatarUpdated;
use App\Domains\Users\Events\UserCreated;
use App\Domains\Users\Events\UserDeleted;
use App\Domains\Users\Events\UserRestored;
use App\Domains\Users\Events\UserUpdated;
use App\Domains\Users\Listeners\LogUserActivity;
use App\Domains\Users\Listeners\PrepareUserNotifications;
use App\Domains\Users\Policies\UserPolicy;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionBillingGatewayInterface::class, function ($app) {
            return match (config('billing.default_provider', 'manual')) {
                'stripe' => $app->make(StripeBillingGateway::class),
                default => $app->make(ManualBillingGateway::class),
            };
        });
    }

    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });

        $this->configureRateLimiting();
        $this->configureAuthEvents();
        $this->configureUserEvents();
        $this->configureRoleEvents();
        $this->configureCompanyEvents();
        $this->configureCustomerEvents();
        $this->configureApplicationEvents();
        $this->configureContentEvents();
        $this->configureIntegrationEvents();
        $this->configureSupportEvents();
        $this->configureComplianceEvents();
        $this->configureNotificationEvents();
        $this->configureAutomationEvents();
        $this->configureWorkflowEvents();
        $this->configureSchedulerEvents();
        $this->configureAiEvents();
        $this->configureAnalyticsEvents();
        $this->configureSettingsEvents();
        $this->configureAuditEvents();
        $this->configurePolicies();
        $this->configurePasswordResetUrls();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('webhook-incoming', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('auth-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }

    protected function configureAuthEvents(): void
    {
        $listener = LogAuthenticationActivity::class;

        Event::listen(UserLoggedIn::class, [$listener, 'handleUserLoggedIn']);
        Event::listen(UserLoggedOut::class, [$listener, 'handleUserLoggedOut']);
        Event::listen(PasswordChanged::class, [$listener, 'handlePasswordChanged']);
        Event::listen(PasswordResetRequested::class, [$listener, 'handlePasswordResetRequested']);
        Event::listen(PasswordResetCompleted::class, [$listener, 'handlePasswordResetCompleted']);
        Event::listen(EmailVerified::class, [$listener, 'handleEmailVerified']);

        Event::listen(UserLoggedIn::class, [RecordLoginHistory::class, 'handleUserLoggedIn']);
        Event::listen(UserLoggedOut::class, [RecordLoginHistory::class, 'handleUserLoggedOut']);
    }

    protected function configureUserEvents(): void
    {
        $activity = LogUserActivity::class;
        $notifications = PrepareUserNotifications::class;

        Event::listen(UserCreated::class, [$activity, 'handleUserCreated']);
        Event::listen(UserUpdated::class, [$activity, 'handleUserUpdated']);
        Event::listen(UserDeleted::class, [$activity, 'handleUserDeleted']);
        Event::listen(UserRestored::class, [$activity, 'handleUserRestored']);
        Event::listen(AvatarUpdated::class, [$activity, 'handleAvatarUpdated']);

        Event::listen(UserCreated::class, [$notifications, 'handleUserCreated']);
        Event::listen(UserUpdated::class, [$notifications, 'handleUserUpdated']);
        Event::listen(UserDeleted::class, [$notifications, 'handleUserDeleted']);
        Event::listen(UserRestored::class, [$notifications, 'handleUserRestored']);
        Event::listen(AvatarUpdated::class, [$notifications, 'handleAvatarUpdated']);
    }

    protected function configureRoleEvents(): void
    {
        $activity = LogRoleActivity::class;
        $notifications = PrepareRoleNotifications::class;

        Event::listen(RoleCreated::class, [$activity, 'handleRoleCreated']);
        Event::listen(RoleUpdated::class, [$activity, 'handleRoleUpdated']);
        Event::listen(RoleDeleted::class, [$activity, 'handleRoleDeleted']);
        Event::listen(PermissionAssigned::class, [$activity, 'handlePermissionAssigned']);
        Event::listen(PermissionRemoved::class, [$activity, 'handlePermissionRemoved']);
        Event::listen(UserRoleAssigned::class, [$activity, 'handleUserRoleAssigned']);
        Event::listen(UserRoleRemoved::class, [$activity, 'handleUserRoleRemoved']);

        Event::listen(RoleCreated::class, [$notifications, 'handleRoleCreated']);
        Event::listen(RoleUpdated::class, [$notifications, 'handleRoleUpdated']);
        Event::listen(RoleDeleted::class, [$notifications, 'handleRoleDeleted']);
        Event::listen(PermissionAssigned::class, [$notifications, 'handlePermissionAssigned']);
        Event::listen(PermissionRemoved::class, [$notifications, 'handlePermissionRemoved']);
        Event::listen(UserRoleAssigned::class, [$notifications, 'handleUserRoleAssigned']);
        Event::listen(UserRoleRemoved::class, [$notifications, 'handleUserRoleRemoved']);
    }

    protected function configureCompanyEvents(): void
    {
        $activity = LogCompanyActivity::class;
        $notifications = PrepareCompanyNotifications::class;

        Event::listen(CompanyCreated::class, [$activity, 'handleCompanyCreated']);
        Event::listen(CompanyUpdated::class, [$activity, 'handleCompanyUpdated']);
        Event::listen(CompanyDeleted::class, [$activity, 'handleCompanyDeleted']);
        Event::listen(BrandingUpdated::class, [$activity, 'handleBrandingUpdated']);
        Event::listen(DepartmentCreated::class, [$activity, 'handleDepartmentCreated']);
        Event::listen(DepartmentUpdated::class, [$activity, 'handleDepartmentUpdated']);
        Event::listen(TeamCreated::class, [$activity, 'handleTeamCreated']);
        Event::listen(LocationCreated::class, [$activity, 'handleLocationCreated']);

        Event::listen(CompanyCreated::class, [$notifications, 'handleCompanyCreated']);
        Event::listen(CompanyUpdated::class, [$notifications, 'handleCompanyUpdated']);
        Event::listen(CompanyDeleted::class, [$notifications, 'handleCompanyDeleted']);
        Event::listen(BrandingUpdated::class, [$notifications, 'handleBrandingUpdated']);
        Event::listen(DepartmentCreated::class, [$notifications, 'handleDepartmentCreated']);
        Event::listen(DepartmentUpdated::class, [$notifications, 'handleDepartmentUpdated']);
        Event::listen(TeamCreated::class, [$notifications, 'handleTeamCreated']);
        Event::listen(LocationCreated::class, [$notifications, 'handleLocationCreated']);
    }

    protected function configureCustomerEvents(): void
    {
        $activity = LogCustomerActivity::class;
        $notifications = PrepareCustomerNotifications::class;

        Event::listen(CustomerCreated::class, [$activity, 'handleCustomerCreated']);
        Event::listen(CustomerUpdated::class, [$activity, 'handleCustomerUpdated']);
        Event::listen(CustomerDeleted::class, [$activity, 'handleCustomerDeleted']);
        Event::listen(CustomerRestored::class, [$activity, 'handleCustomerRestored']);

        Event::listen(CustomerCreated::class, [$notifications, 'handleCustomerCreated']);
        Event::listen(CustomerUpdated::class, [$notifications, 'handleCustomerUpdated']);
        Event::listen(CustomerDeleted::class, [$notifications, 'handleCustomerDeleted']);
        Event::listen(CustomerRestored::class, [$notifications, 'handleCustomerRestored']);

        $contactActivity = LogCustomerContactActivity::class;
        $contactNotifications = PrepareCustomerContactNotifications::class;

        Event::listen(CustomerContactCreated::class, [$contactActivity, 'handleCustomerContactCreated']);
        Event::listen(CustomerContactUpdated::class, [$contactActivity, 'handleCustomerContactUpdated']);
        Event::listen(CustomerContactDeleted::class, [$contactActivity, 'handleCustomerContactDeleted']);
        Event::listen(CustomerContactRestored::class, [$contactActivity, 'handleCustomerContactRestored']);

        Event::listen(CustomerContactCreated::class, [$contactNotifications, 'handleCustomerContactCreated']);
        Event::listen(CustomerContactUpdated::class, [$contactNotifications, 'handleCustomerContactUpdated']);
        Event::listen(CustomerContactDeleted::class, [$contactNotifications, 'handleCustomerContactDeleted']);
        Event::listen(CustomerContactRestored::class, [$contactNotifications, 'handleCustomerContactRestored']);

        $assignmentActivity = LogCustomerApplicationActivity::class;
        $assignmentNotifications = PrepareCustomerApplicationNotifications::class;

        Event::listen(CustomerApplicationAssigned::class, [$assignmentActivity, 'handleCustomerApplicationAssigned']);
        Event::listen(CustomerApplicationUpdated::class, [$assignmentActivity, 'handleCustomerApplicationUpdated']);
        Event::listen(CustomerApplicationDeleted::class, [$assignmentActivity, 'handleCustomerApplicationDeleted']);
        Event::listen(CustomerApplicationRestored::class, [$assignmentActivity, 'handleCustomerApplicationRestored']);

        Event::listen(CustomerApplicationAssigned::class, [$assignmentNotifications, 'handleCustomerApplicationAssigned']);
        Event::listen(CustomerApplicationUpdated::class, [$assignmentNotifications, 'handleCustomerApplicationUpdated']);
        Event::listen(CustomerApplicationDeleted::class, [$assignmentNotifications, 'handleCustomerApplicationDeleted']);
        Event::listen(CustomerApplicationRestored::class, [$assignmentNotifications, 'handleCustomerApplicationRestored']);

        $subscriptionActivity = LogSubscriptionActivity::class;
        $subscriptionNotifications = PrepareSubscriptionNotifications::class;

        Event::listen(SubscriptionCreated::class, [$subscriptionActivity, 'handleSubscriptionCreated']);
        Event::listen(SubscriptionUpdated::class, [$subscriptionActivity, 'handleSubscriptionUpdated']);
        Event::listen(SubscriptionCancelled::class, [$subscriptionActivity, 'handleSubscriptionCancelled']);
        Event::listen(SubscriptionDeleted::class, [$subscriptionActivity, 'handleSubscriptionDeleted']);
        Event::listen(SubscriptionRestored::class, [$subscriptionActivity, 'handleSubscriptionRestored']);

        Event::listen(SubscriptionCreated::class, [$subscriptionNotifications, 'handleSubscriptionCreated']);
        Event::listen(SubscriptionUpdated::class, [$subscriptionNotifications, 'handleSubscriptionUpdated']);
        Event::listen(SubscriptionCancelled::class, [$subscriptionNotifications, 'handleSubscriptionCancelled']);
        Event::listen(SubscriptionDeleted::class, [$subscriptionNotifications, 'handleSubscriptionDeleted']);
        Event::listen(SubscriptionRestored::class, [$subscriptionNotifications, 'handleSubscriptionRestored']);

        $licenseActivity = LogLicenseActivity::class;
        $licenseNotifications = PrepareLicenseNotifications::class;

        Event::listen(LicenseCreated::class, [$licenseActivity, 'handleLicenseCreated']);
        Event::listen(LicenseUpdated::class, [$licenseActivity, 'handleLicenseUpdated']);
        Event::listen(LicenseRevoked::class, [$licenseActivity, 'handleLicenseRevoked']);
        Event::listen(LicenseDeleted::class, [$licenseActivity, 'handleLicenseDeleted']);
        Event::listen(LicenseRestored::class, [$licenseActivity, 'handleLicenseRestored']);

        Event::listen(LicenseCreated::class, [$licenseNotifications, 'handleLicenseCreated']);
        Event::listen(LicenseUpdated::class, [$licenseNotifications, 'handleLicenseUpdated']);
        Event::listen(LicenseRevoked::class, [$licenseNotifications, 'handleLicenseRevoked']);
        Event::listen(LicenseDeleted::class, [$licenseNotifications, 'handleLicenseDeleted']);
        Event::listen(LicenseRestored::class, [$licenseNotifications, 'handleLicenseRestored']);

        $documentActivity = LogCustomerDocumentActivity::class;
        $documentNotifications = PrepareCustomerDocumentNotifications::class;

        Event::listen(CustomerDocumentUploaded::class, [$documentActivity, 'handleCustomerDocumentUploaded']);
        Event::listen(CustomerDocumentVersionUploaded::class, [$documentActivity, 'handleCustomerDocumentVersionUploaded']);
        Event::listen(CustomerDocumentUpdated::class, [$documentActivity, 'handleCustomerDocumentUpdated']);
        Event::listen(CustomerDocumentDeleted::class, [$documentActivity, 'handleCustomerDocumentDeleted']);
        Event::listen(CustomerDocumentRestored::class, [$documentActivity, 'handleCustomerDocumentRestored']);

        Event::listen(CustomerDocumentUploaded::class, [$documentNotifications, 'handleCustomerDocumentUploaded']);
        Event::listen(CustomerDocumentVersionUploaded::class, [$documentNotifications, 'handleCustomerDocumentVersionUploaded']);
        Event::listen(CustomerDocumentUpdated::class, [$documentNotifications, 'handleCustomerDocumentUpdated']);
        Event::listen(CustomerDocumentDeleted::class, [$documentNotifications, 'handleCustomerDocumentDeleted']);
        Event::listen(CustomerDocumentRestored::class, [$documentNotifications, 'handleCustomerDocumentRestored']);

        $commActivity = LogCustomerCommunicationCenterActivity::class;
        $commNotifications = PrepareCustomerCommunicationCenterNotifications::class;

        Event::listen(CustomerNoteCreated::class, [$commActivity, 'handleCustomerNoteCreated']);
        Event::listen(CustomerNoteUpdated::class, [$commActivity, 'handleCustomerNoteUpdated']);
        Event::listen(CustomerNoteDeleted::class, [$commActivity, 'handleCustomerNoteDeleted']);
        Event::listen(CustomerNoteRestored::class, [$commActivity, 'handleCustomerNoteRestored']);
        Event::listen(CustomerTaskCreated::class, [$commActivity, 'handleCustomerTaskCreated']);
        Event::listen(CustomerTaskUpdated::class, [$commActivity, 'handleCustomerTaskUpdated']);
        Event::listen(CustomerTaskCompleted::class, [$commActivity, 'handleCustomerTaskCompleted']);
        Event::listen(CustomerTaskDeleted::class, [$commActivity, 'handleCustomerTaskDeleted']);
        Event::listen(CustomerTaskRestored::class, [$commActivity, 'handleCustomerTaskRestored']);
        Event::listen(CustomerCommunicationCreated::class, [$commActivity, 'handleCustomerCommunicationCreated']);
        Event::listen(CustomerCommunicationUpdated::class, [$commActivity, 'handleCustomerCommunicationUpdated']);
        Event::listen(CustomerCommunicationDeleted::class, [$commActivity, 'handleCustomerCommunicationDeleted']);
        Event::listen(CustomerCommunicationRestored::class, [$commActivity, 'handleCustomerCommunicationRestored']);

        Event::listen(CustomerNoteCreated::class, [$commNotifications, 'handleCustomerNoteCreated']);
        Event::listen(CustomerNoteUpdated::class, [$commNotifications, 'handleCustomerNoteUpdated']);
        Event::listen(CustomerNoteDeleted::class, [$commNotifications, 'handleCustomerNoteDeleted']);
        Event::listen(CustomerNoteRestored::class, [$commNotifications, 'handleCustomerNoteRestored']);
        Event::listen(CustomerTaskCreated::class, [$commNotifications, 'handleCustomerTaskCreated']);
        Event::listen(CustomerTaskUpdated::class, [$commNotifications, 'handleCustomerTaskUpdated']);
        Event::listen(CustomerTaskCompleted::class, [$commNotifications, 'handleCustomerTaskCompleted']);
        Event::listen(CustomerTaskDeleted::class, [$commNotifications, 'handleCustomerTaskDeleted']);
        Event::listen(CustomerTaskRestored::class, [$commNotifications, 'handleCustomerTaskRestored']);
        Event::listen(CustomerCommunicationCreated::class, [$commNotifications, 'handleCustomerCommunicationCreated']);
        Event::listen(CustomerCommunicationUpdated::class, [$commNotifications, 'handleCustomerCommunicationUpdated']);
        Event::listen(CustomerCommunicationDeleted::class, [$commNotifications, 'handleCustomerCommunicationDeleted']);
        Event::listen(CustomerCommunicationRestored::class, [$commNotifications, 'handleCustomerCommunicationRestored']);

        Event::listen(CustomerAnalyticsSnapshotComputed::class, [LogCustomerAnalyticsActivity::class, 'handleCustomerAnalyticsSnapshotComputed']);
    }

    protected function configureApplicationEvents(): void
    {
        $activity = LogApplicationActivity::class;
        $notifications = PrepareApplicationNotifications::class;

        Event::listen(ApplicationCreated::class, [$activity, 'handleApplicationCreated']);
        Event::listen(ApplicationUpdated::class, [$activity, 'handleApplicationUpdated']);
        Event::listen(ApplicationDeleted::class, [$activity, 'handleApplicationDeleted']);
        Event::listen(ApplicationRestored::class, [$activity, 'handleApplicationRestored']);
        Event::listen(ApplicationVersionCreated::class, [$activity, 'handleApplicationVersionCreated']);
        Event::listen(ApplicationVersionUpdated::class, [$activity, 'handleApplicationVersionUpdated']);
        Event::listen(ApplicationVersionDeleted::class, [$activity, 'handleApplicationVersionDeleted']);
        Event::listen(ApplicationEnvironmentCreated::class, [$activity, 'handleApplicationEnvironmentCreated']);
        Event::listen(ApplicationEnvironmentUpdated::class, [$activity, 'handleApplicationEnvironmentUpdated']);
        Event::listen(ApplicationEnvironmentDeleted::class, [$activity, 'handleApplicationEnvironmentDeleted']);
        Event::listen(ApplicationEnvironmentSwitched::class, [$activity, 'handleApplicationEnvironmentSwitched']);
        Event::listen(ApplicationEnvironmentHealthChecked::class, [$activity, 'handleApplicationEnvironmentHealthChecked']);
        Event::listen(ApplicationConfigurationCreated::class, [$activity, 'handleApplicationConfigurationCreated']);
        Event::listen(ApplicationConfigurationUpdated::class, [$activity, 'handleApplicationConfigurationUpdated']);
        Event::listen(ApplicationConfigurationDeleted::class, [$activity, 'handleApplicationConfigurationDeleted']);
        Event::listen(ApplicationConfigurationRestoredHistory::class, [$activity, 'handleApplicationConfigurationRestoredHistory']);
        Event::listen(ApplicationReleaseCreated::class, [$activity, 'handleApplicationReleaseCreated']);
        Event::listen(ApplicationReleaseUpdated::class, [$activity, 'handleApplicationReleaseUpdated']);
        Event::listen(ApplicationReleaseDeleted::class, [$activity, 'handleApplicationReleaseDeleted']);
        Event::listen(ApplicationReleaseApproved::class, [$activity, 'handleApplicationReleaseApproved']);
        Event::listen(ApplicationReleaseRejected::class, [$activity, 'handleApplicationReleaseRejected']);
        Event::listen(ApplicationReleaseDeployed::class, [$activity, 'handleApplicationReleaseDeployed']);
        Event::listen(ApplicationReleaseRolledBack::class, [$activity, 'handleApplicationReleaseRolledBack']);
        Event::listen(ApplicationCrashReported::class, [$activity, 'handleApplicationCrashReported']);
        Event::listen(ApplicationCrashUpdated::class, [$activity, 'handleApplicationCrashUpdated']);
        Event::listen(ApplicationHealthMetricRecorded::class, [$activity, 'handleApplicationHealthMetricRecorded']);
        Event::listen(ApplicationMonitoringAlertTriggered::class, [$activity, 'handleApplicationMonitoringAlertTriggered']);
        Event::listen(ApplicationAnalyticsIngested::class, [$activity, 'handleApplicationAnalyticsIngested']);

        Event::listen(ApplicationCreated::class, [$notifications, 'handleApplicationCreated']);
        Event::listen(ApplicationUpdated::class, [$notifications, 'handleApplicationUpdated']);
        Event::listen(ApplicationDeleted::class, [$notifications, 'handleApplicationDeleted']);
        Event::listen(ApplicationRestored::class, [$notifications, 'handleApplicationRestored']);
        Event::listen(ApplicationVersionCreated::class, [$notifications, 'handleApplicationVersionCreated']);
        Event::listen(ApplicationVersionUpdated::class, [$notifications, 'handleApplicationVersionUpdated']);
        Event::listen(ApplicationVersionDeleted::class, [$notifications, 'handleApplicationVersionDeleted']);
        Event::listen(ApplicationEnvironmentCreated::class, [$notifications, 'handleApplicationEnvironmentCreated']);
        Event::listen(ApplicationEnvironmentUpdated::class, [$notifications, 'handleApplicationEnvironmentUpdated']);
        Event::listen(ApplicationEnvironmentDeleted::class, [$notifications, 'handleApplicationEnvironmentDeleted']);
        Event::listen(ApplicationEnvironmentSwitched::class, [$notifications, 'handleApplicationEnvironmentSwitched']);
        Event::listen(ApplicationEnvironmentHealthChecked::class, [$notifications, 'handleApplicationEnvironmentHealthChecked']);
        Event::listen(ApplicationConfigurationCreated::class, [$notifications, 'handleApplicationConfigurationCreated']);
        Event::listen(ApplicationConfigurationUpdated::class, [$notifications, 'handleApplicationConfigurationUpdated']);
        Event::listen(ApplicationConfigurationDeleted::class, [$notifications, 'handleApplicationConfigurationDeleted']);
        Event::listen(ApplicationConfigurationRestoredHistory::class, [$notifications, 'handleApplicationConfigurationRestoredHistory']);
        Event::listen(ApplicationReleaseCreated::class, [$notifications, 'handleApplicationReleaseCreated']);
        Event::listen(ApplicationReleaseUpdated::class, [$notifications, 'handleApplicationReleaseUpdated']);
        Event::listen(ApplicationReleaseDeleted::class, [$notifications, 'handleApplicationReleaseDeleted']);
        Event::listen(ApplicationReleaseApproved::class, [$notifications, 'handleApplicationReleaseApproved']);
        Event::listen(ApplicationReleaseRejected::class, [$notifications, 'handleApplicationReleaseRejected']);
        Event::listen(ApplicationReleaseDeployed::class, [$notifications, 'handleApplicationReleaseDeployed']);
        Event::listen(ApplicationReleaseRolledBack::class, [$notifications, 'handleApplicationReleaseRolledBack']);
        Event::listen(ApplicationCrashReported::class, [$notifications, 'handleApplicationCrashReported']);
        Event::listen(ApplicationCrashUpdated::class, [$notifications, 'handleApplicationCrashUpdated']);
        Event::listen(ApplicationHealthMetricRecorded::class, [$notifications, 'handleApplicationHealthMetricRecorded']);
        Event::listen(ApplicationMonitoringAlertTriggered::class, [$notifications, 'handleApplicationMonitoringAlertTriggered']);
        Event::listen(ApplicationAnalyticsIngested::class, [$notifications, 'handleApplicationAnalyticsIngested']);
    }

    protected function configureContentEvents(): void
    {
        $activity = LogContentActivity::class;
        $notifications = PrepareContentNotifications::class;

        Event::listen(ContentCreated::class, [$activity, 'handleContentCreated']);
        Event::listen(ContentUpdated::class, [$activity, 'handleContentUpdated']);
        Event::listen(ContentDeleted::class, [$activity, 'handleContentDeleted']);
        Event::listen(ContentRestored::class, [$activity, 'handleContentRestored']);
        Event::listen(ContentPublished::class, [$activity, 'handleContentPublished']);
        Event::listen(ContentUnpublished::class, [$activity, 'handleContentUnpublished']);
        Event::listen(ContentVersionRestored::class, [$activity, 'handleContentVersionRestored']);
        Event::listen(ContentWorkflowTransitioned::class, [$activity, 'handleContentWorkflowTransitioned']);
        Event::listen(MediaLibraryUploaded::class, [$activity, 'handleMediaLibraryUploaded']);
        Event::listen(MediaLibraryReplaced::class, [$activity, 'handleMediaLibraryReplaced']);
        Event::listen(MediaLibraryDeleted::class, [$activity, 'handleMediaLibraryDeleted']);

        Event::listen(ContentCreated::class, [$notifications, 'handleContentCreated']);
        Event::listen(ContentUpdated::class, [$notifications, 'handleContentUpdated']);
        Event::listen(ContentDeleted::class, [$notifications, 'handleContentDeleted']);
        Event::listen(ContentRestored::class, [$notifications, 'handleContentRestored']);
        Event::listen(ContentPublished::class, [$notifications, 'handleContentPublished']);
        Event::listen(ContentUnpublished::class, [$notifications, 'handleContentUnpublished']);
        Event::listen(ContentVersionRestored::class, [$notifications, 'handleContentVersionRestored']);
        Event::listen(ContentWorkflowTransitioned::class, [$notifications, 'handleContentWorkflowTransitioned']);
    }

    protected function configureIntegrationEvents(): void
    {
        $activity = LogIntegrationActivity::class;
        $notifications = PrepareIntegrationNotifications::class;

        Event::listen(IntegrationCreated::class, [$activity, 'handleIntegrationCreated']);
        Event::listen(IntegrationUpdated::class, [$activity, 'handleIntegrationUpdated']);
        Event::listen(IntegrationDeleted::class, [$activity, 'handleIntegrationDeleted']);
        Event::listen(IntegrationRestored::class, [$activity, 'handleIntegrationRestored']);
        Event::listen(IntegrationConfigurationUpdated::class, [$activity, 'handleConfigurationUpdated']);
        Event::listen(IntegrationConnectionExecuted::class, [$activity, 'handleConnectionExecuted']);

        Event::listen(IntegrationCreated::class, [$notifications, 'handleIntegrationCreated']);
        Event::listen(IntegrationUpdated::class, [$notifications, 'handleIntegrationUpdated']);
        Event::listen(IntegrationDeleted::class, [$notifications, 'handleIntegrationDeleted']);
        Event::listen(IntegrationRestored::class, [$notifications, 'handleIntegrationRestored']);
        Event::listen(IntegrationConfigurationUpdated::class, [$notifications, 'handleConfigurationUpdated']);
        Event::listen(IntegrationConnectionExecuted::class, [$notifications, 'handleConnectionExecuted']);
        Event::listen(WebhookCreated::class, [$activity, 'handleWebhookCreated']);
        Event::listen(WebhookUpdated::class, [$activity, 'handleWebhookUpdated']);
        Event::listen(WebhookDeleted::class, [$activity, 'handleWebhookDeleted']);
        Event::listen(WebhookDelivered::class, [$activity, 'handleWebhookDelivered']);
        Event::listen(WebhookFailed::class, [$activity, 'handleWebhookFailed']);
        Event::listen(WebhookCreated::class, [$notifications, 'handleWebhookCreated']);
        Event::listen(WebhookUpdated::class, [$notifications, 'handleWebhookUpdated']);
        Event::listen(WebhookDeleted::class, [$notifications, 'handleWebhookDeleted']);
        Event::listen(WebhookDelivered::class, [$notifications, 'handleWebhookDelivered']);
        Event::listen(WebhookFailed::class, [$notifications, 'handleWebhookFailed']);
        Event::listen(SyncRunStarted::class, [$activity, 'handleSyncRunStarted']);
        Event::listen(SyncRunCompleted::class, [$activity, 'handleSyncRunCompleted']);
        Event::listen(SyncRunFailed::class, [$activity, 'handleSyncRunFailed']);
        Event::listen(SyncRunStarted::class, [$notifications, 'handleSyncRunStarted']);
        Event::listen(SyncRunCompleted::class, [$notifications, 'handleSyncRunCompleted']);
        Event::listen(SyncRunFailed::class, [$notifications, 'handleSyncRunFailed']);
        Event::listen(DataMappingCreated::class, [$activity, 'handleDataMappingCreated']);
        Event::listen(DataMappingUpdated::class, [$activity, 'handleDataMappingUpdated']);
        Event::listen(DataMappingDeleted::class, [$activity, 'handleDataMappingDeleted']);
        Event::listen(DataMappingCreated::class, [$notifications, 'handleDataMappingCreated']);
        Event::listen(DataMappingUpdated::class, [$notifications, 'handleDataMappingUpdated']);
        Event::listen(DataMappingDeleted::class, [$notifications, 'handleDataMappingDeleted']);
    }

    protected function configureSupportEvents(): void
    {
        $activity = LogSupportActivity::class;
        $notifications = PrepareSupportNotifications::class;

        Event::listen(SupportTicketCreated::class, [$activity, 'handleSupportTicketCreated']);
        Event::listen(SupportTicketCreated::class, RoutePersonalDataTicketToCompliance::class);
        Event::listen(SupportTicketUpdated::class, [$activity, 'handleSupportTicketUpdated']);
        Event::listen(SupportTicketDeleted::class, [$activity, 'handleSupportTicketDeleted']);
        Event::listen(SupportTicketRestored::class, [$activity, 'handleSupportTicketRestored']);
        Event::listen(SupportTicketAssigned::class, [$activity, 'handleSupportTicketAssigned']);
        Event::listen(SupportTicketClosed::class, [$activity, 'handleSupportTicketClosed']);
        Event::listen(SupportTicketReopened::class, [$activity, 'handleSupportTicketReopened']);
        Event::listen(SupportTicketStatusChanged::class, [$activity, 'handleSupportTicketStatusChanged']);
        Event::listen(SupportTicketMessageCreated::class, [$activity, 'handleSupportTicketMessageCreated']);
        Event::listen(SupportTicketAttachmentUploaded::class, [$activity, 'handleSupportTicketAttachmentUploaded']);
        Event::listen(SupportTicketSlaBreached::class, [$activity, 'handleSupportTicketSlaBreached']);
        Event::listen(SupportTicketSlaWarning::class, [$activity, 'handleSupportTicketSlaWarning']);
        Event::listen(SupportTicketSlaEscalated::class, [$activity, 'handleSupportTicketSlaEscalated']);

        Event::listen(SupportTicketCreated::class, [$notifications, 'handleSupportTicketCreated']);
        Event::listen(SupportTicketUpdated::class, [$notifications, 'handleSupportTicketUpdated']);
        Event::listen(SupportTicketDeleted::class, [$notifications, 'handleSupportTicketDeleted']);
        Event::listen(SupportTicketRestored::class, [$notifications, 'handleSupportTicketRestored']);
        Event::listen(SupportTicketAssigned::class, [$notifications, 'handleSupportTicketAssigned']);
        Event::listen(SupportTicketClosed::class, [$notifications, 'handleSupportTicketClosed']);
        Event::listen(SupportTicketReopened::class, [$notifications, 'handleSupportTicketReopened']);
        Event::listen(SupportTicketStatusChanged::class, [$notifications, 'handleSupportTicketStatusChanged']);
        Event::listen(SupportTicketMessageCreated::class, [$notifications, 'handleSupportTicketMessageCreated']);
        Event::listen(SupportTicketAttachmentUploaded::class, [$notifications, 'handleSupportTicketAttachmentUploaded']);
        Event::listen(SupportTicketSlaBreached::class, [$notifications, 'handleSupportTicketSlaBreached']);
        Event::listen(SupportTicketSlaWarning::class, [$notifications, 'handleSupportTicketSlaWarning']);
        Event::listen(SupportTicketSlaEscalated::class, [$notifications, 'handleSupportTicketSlaEscalated']);
    }

    protected function configureComplianceEvents(): void
    {
        $activity = LogComplianceActivity::class;
        $notifications = PrepareComplianceNotifications::class;

        Event::listen(ComplianceCaseCreated::class, [$activity, 'handleComplianceCaseCreated']);
        Event::listen(ComplianceCaseUpdated::class, [$activity, 'handleComplianceCaseUpdated']);
        Event::listen(ComplianceCaseDeleted::class, [$activity, 'handleComplianceCaseDeleted']);
        Event::listen(ComplianceCaseRestored::class, [$activity, 'handleComplianceCaseRestored']);
        Event::listen(ComplianceCaseAssigned::class, [$activity, 'handleComplianceCaseAssigned']);

        Event::listen(ComplianceCaseCreated::class, [$notifications, 'handleComplianceCaseCreated']);
        Event::listen(ComplianceCaseUpdated::class, [$notifications, 'handleComplianceCaseUpdated']);
        Event::listen(ComplianceCaseDeleted::class, [$notifications, 'handleComplianceCaseDeleted']);
        Event::listen(ComplianceCaseRestored::class, [$notifications, 'handleComplianceCaseRestored']);
        Event::listen(ComplianceCaseAssigned::class, [$notifications, 'handleComplianceCaseAssigned']);

        $privacyActivity = LogPrivacyRequestActivity::class;
        $privacyNotifications = PreparePrivacyRequestNotifications::class;

        Event::listen(PrivacyRequestCreated::class, [$privacyActivity, 'handlePrivacyRequestCreated']);
        Event::listen(PrivacyRequestUpdated::class, [$privacyActivity, 'handlePrivacyRequestUpdated']);
        Event::listen(PrivacyRequestAssigned::class, [$privacyActivity, 'handlePrivacyRequestAssigned']);
        Event::listen(PrivacyRequestStatusChanged::class, [$privacyActivity, 'handlePrivacyRequestStatusChanged']);
        Event::listen(PrivacyRequestIdentityVerified::class, [$privacyActivity, 'handlePrivacyRequestIdentityVerified']);
        Event::listen(PrivacyRequestApproved::class, [$privacyActivity, 'handlePrivacyRequestApproved']);
        Event::listen(PrivacyRequestRejected::class, [$privacyActivity, 'handlePrivacyRequestRejected']);
        Event::listen(PrivacyRequestExportGenerated::class, [$privacyActivity, 'handlePrivacyRequestExportGenerated']);
        Event::listen(PrivacyRequestDataDeleted::class, [$privacyActivity, 'handlePrivacyRequestDataDeleted']);
        Event::listen(PrivacyRequestCompleted::class, [$privacyActivity, 'handlePrivacyRequestCompleted']);

        Event::listen(PrivacyRequestCreated::class, [$privacyNotifications, 'handlePrivacyRequestCreated']);
        Event::listen(PrivacyRequestUpdated::class, [$privacyNotifications, 'handlePrivacyRequestUpdated']);
        Event::listen(PrivacyRequestAssigned::class, [$privacyNotifications, 'handlePrivacyRequestAssigned']);
        Event::listen(PrivacyRequestStatusChanged::class, [$privacyNotifications, 'handlePrivacyRequestStatusChanged']);
        Event::listen(PrivacyRequestIdentityVerified::class, [$privacyNotifications, 'handlePrivacyRequestIdentityVerified']);
        Event::listen(PrivacyRequestApproved::class, [$privacyNotifications, 'handlePrivacyRequestApproved']);
        Event::listen(PrivacyRequestRejected::class, [$privacyNotifications, 'handlePrivacyRequestRejected']);
        Event::listen(PrivacyRequestExportGenerated::class, [$privacyNotifications, 'handlePrivacyRequestExportGenerated']);
        Event::listen(PrivacyRequestDataDeleted::class, [$privacyNotifications, 'handlePrivacyRequestDataDeleted']);
        Event::listen(PrivacyRequestCompleted::class, [$privacyNotifications, 'handlePrivacyRequestCompleted']);

        $consentActivity = LogConsentActivity::class;
        $consentNotifications = PrepareConsentNotifications::class;

        Event::listen(ConsentGranted::class, [$consentActivity, 'handleConsentGranted']);
        Event::listen(ConsentWithdrawn::class, [$consentActivity, 'handleConsentWithdrawn']);
        Event::listen(ConsentUpdated::class, [$consentActivity, 'handleConsentUpdated']);

        Event::listen(ConsentGranted::class, [$consentNotifications, 'handleConsentGranted']);
        Event::listen(ConsentWithdrawn::class, [$consentNotifications, 'handleConsentWithdrawn']);
        Event::listen(ConsentUpdated::class, [$consentNotifications, 'handleConsentUpdated']);

        $breachActivity = LogDataBreachActivity::class;
        $breachNotifications = PrepareDataBreachNotifications::class;

        Event::listen(DataBreachCreated::class, [$breachActivity, 'handleDataBreachCreated']);
        Event::listen(DataBreachUpdated::class, [$breachActivity, 'handleDataBreachUpdated']);
        Event::listen(DataBreachAssigned::class, [$breachActivity, 'handleDataBreachAssigned']);
        Event::listen(DataBreachStatusChanged::class, [$breachActivity, 'handleDataBreachStatusChanged']);
        Event::listen(DataBreachRiskAssessed::class, [$breachActivity, 'handleDataBreachRiskAssessed']);
        Event::listen(DataBreachContained::class, [$breachActivity, 'handleDataBreachContained']);
        Event::listen(DataBreachRecovered::class, [$breachActivity, 'handleDataBreachRecovered']);
        Event::listen(DataBreachClosed::class, [$breachActivity, 'handleDataBreachClosed']);
        Event::listen(DataBreachDeleted::class, [$breachActivity, 'handleDataBreachDeleted']);
        Event::listen(DataBreachRestored::class, [$breachActivity, 'handleDataBreachRestored']);
        Event::listen(DataBreachActionRecorded::class, [$breachActivity, 'handleDataBreachActionRecorded']);
        Event::listen(DataBreachNotificationSent::class, [$breachActivity, 'handleDataBreachNotificationSent']);

        Event::listen(DataBreachCreated::class, [$breachNotifications, 'handleDataBreachCreated']);
        Event::listen(DataBreachUpdated::class, [$breachNotifications, 'handleDataBreachUpdated']);
        Event::listen(DataBreachAssigned::class, [$breachNotifications, 'handleDataBreachAssigned']);
        Event::listen(DataBreachStatusChanged::class, [$breachNotifications, 'handleDataBreachStatusChanged']);
        Event::listen(DataBreachRiskAssessed::class, [$breachNotifications, 'handleDataBreachRiskAssessed']);
        Event::listen(DataBreachContained::class, [$breachNotifications, 'handleDataBreachContained']);
        Event::listen(DataBreachRecovered::class, [$breachNotifications, 'handleDataBreachRecovered']);
        Event::listen(DataBreachClosed::class, [$breachNotifications, 'handleDataBreachClosed']);
        Event::listen(DataBreachDeleted::class, [$breachNotifications, 'handleDataBreachDeleted']);
        Event::listen(DataBreachRestored::class, [$breachNotifications, 'handleDataBreachRestored']);
        Event::listen(DataBreachActionRecorded::class, [$breachNotifications, 'handleDataBreachActionRecorded']);
        Event::listen(DataBreachNotificationSent::class, [$breachNotifications, 'handleDataBreachNotificationSent']);

        $dpiaActivity = LogDpiaActivity::class;
        $dpiaNotifications = PrepareDpiaNotifications::class;

        Event::listen(DpiaCreated::class, [$dpiaActivity, 'handleDpiaCreated']);
        Event::listen(DpiaUpdated::class, [$dpiaActivity, 'handleDpiaUpdated']);
        Event::listen(DpiaSubmitted::class, [$dpiaActivity, 'handleDpiaSubmitted']);
        Event::listen(DpiaApproved::class, [$dpiaActivity, 'handleDpiaApproved']);
        Event::listen(DpiaRejected::class, [$dpiaActivity, 'handleDpiaRejected']);
        Event::listen(RiskCreated::class, [$dpiaActivity, 'handleRiskCreated']);
        Event::listen(RiskUpdated::class, [$dpiaActivity, 'handleRiskUpdated']);
        Event::listen(RiskActionRecorded::class, [$dpiaActivity, 'handleRiskActionRecorded']);

        Event::listen(DpiaCreated::class, [$dpiaNotifications, 'handleDpiaCreated']);
        Event::listen(DpiaUpdated::class, [$dpiaNotifications, 'handleDpiaUpdated']);
        Event::listen(DpiaSubmitted::class, [$dpiaNotifications, 'handleDpiaSubmitted']);
        Event::listen(DpiaApproved::class, [$dpiaNotifications, 'handleDpiaApproved']);
        Event::listen(DpiaRejected::class, [$dpiaNotifications, 'handleDpiaRejected']);
        Event::listen(RiskCreated::class, [$dpiaNotifications, 'handleRiskCreated']);
        Event::listen(RiskUpdated::class, [$dpiaNotifications, 'handleRiskUpdated']);
        Event::listen(RiskActionRecorded::class, [$dpiaNotifications, 'handleRiskActionRecorded']);

        $policyActivity = LogPolicyDocumentActivity::class;
        $policyNotifications = PreparePolicyDocumentNotifications::class;

        Event::listen(PolicyCreated::class, [$policyActivity, 'handlePolicyCreated']);
        Event::listen(PolicyUpdated::class, [$policyActivity, 'handlePolicyUpdated']);
        Event::listen(PolicySubmittedForReview::class, [$policyActivity, 'handlePolicySubmittedForReview']);
        Event::listen(PolicyApproved::class, [$policyActivity, 'handlePolicyApproved']);
        Event::listen(PolicyRejected::class, [$policyActivity, 'handlePolicyRejected']);
        Event::listen(PolicyPublished::class, [$policyActivity, 'handlePolicyPublished']);
        Event::listen(PolicyVersionRestored::class, [$policyActivity, 'handlePolicyVersionRestored']);

        Event::listen(PolicyCreated::class, [$policyNotifications, 'handlePolicyCreated']);
        Event::listen(PolicyUpdated::class, [$policyNotifications, 'handlePolicyUpdated']);
        Event::listen(PolicySubmittedForReview::class, [$policyNotifications, 'handlePolicySubmittedForReview']);
        Event::listen(PolicyApproved::class, [$policyNotifications, 'handlePolicyApproved']);
        Event::listen(PolicyRejected::class, [$policyNotifications, 'handlePolicyRejected']);
        Event::listen(PolicyPublished::class, [$policyNotifications, 'handlePolicyPublished']);
        Event::listen(PolicyVersionRestored::class, [$policyNotifications, 'handlePolicyVersionRestored']);
    }

    protected function configureSettingsEvents(): void
    {
        $activity = LogSettingsActivity::class;
        $notifications = PrepareSettingsNotifications::class;

        Event::listen(SettingsUpdated::class, [$activity, 'handleSettingsUpdated']);
        Event::listen(ConfigurationChanged::class, [$activity, 'handleConfigurationChanged']);
        Event::listen(MediaUploaded::class, [$activity, 'handleMediaUploaded']);
        Event::listen(MediaDeleted::class, [$activity, 'handleMediaDeleted']);
        Event::listen(FolderCreated::class, [$activity, 'handleFolderCreated']);
        Event::listen(FolderDeleted::class, [$activity, 'handleFolderDeleted']);

        Event::listen(SettingsUpdated::class, [$notifications, 'handleSettingsUpdated']);
        Event::listen(ConfigurationChanged::class, [$notifications, 'handleConfigurationChanged']);
        Event::listen(MediaUploaded::class, [$notifications, 'handleMediaUploaded']);
        Event::listen(MediaDeleted::class, [$notifications, 'handleMediaDeleted']);
        Event::listen(FolderCreated::class, [$notifications, 'handleFolderCreated']);
        Event::listen(FolderDeleted::class, [$notifications, 'handleFolderDeleted']);
    }

    protected function configureNotificationEvents(): void
    {
        $activity = LogNotificationActivity::class;

        Event::listen(NotificationCreated::class, [$activity, 'handleNotificationCreated']);
        Event::listen(NotificationRead::class, [$activity, 'handleNotificationRead']);
        Event::listen(NotificationDeleted::class, [$activity, 'handleNotificationDeleted']);
        Event::listen(NotificationTemplateCreated::class, [$activity, 'handleNotificationTemplateCreated']);
        Event::listen(NotificationTemplateUpdated::class, [$activity, 'handleNotificationTemplateUpdated']);
        Event::listen(NotificationTemplateDeleted::class, [$activity, 'handleNotificationTemplateDeleted']);
        Event::listen(NotificationTemplateSubmitted::class, [$activity, 'handleNotificationTemplateSubmitted']);
        Event::listen(NotificationTemplateApproved::class, [$activity, 'handleNotificationTemplateApproved']);
        Event::listen(NotificationTemplateRejected::class, [$activity, 'handleNotificationTemplateRejected']);
        Event::listen(NotificationTemplatePublished::class, [$activity, 'handleNotificationTemplatePublished']);
        Event::listen(NotificationChannelUpdated::class, [$activity, 'handleNotificationChannelUpdated']);
        Event::listen(NotificationPreferencesUpdated::class, [$activity, 'handleNotificationPreferencesUpdated']);
    }

    protected function configureAutomationEvents(): void
    {
        $activity = LogAutomationActivity::class;
        $runner = RunAutomationRules::class;

        Event::listen(AutomationRuleCreated::class, [$activity, 'handleAutomationRuleCreated']);
        Event::listen(AutomationRuleUpdated::class, [$activity, 'handleAutomationRuleUpdated']);
        Event::listen(AutomationRuleDeleted::class, [$activity, 'handleAutomationRuleDeleted']);

        Event::listen(SupportTicketCreated::class, [$runner, 'handleSupportTicketCreated']);
        Event::listen(SupportTicketAssigned::class, [$runner, 'handleSupportTicketAssigned']);
        Event::listen(SupportTicketClosed::class, [$runner, 'handleSupportTicketClosed']);
        Event::listen(CustomerCreated::class, [$runner, 'handleCustomerCreated']);
        Event::listen(ApplicationCreated::class, [$runner, 'handleApplicationCreated']);
        Event::listen(ApplicationReleaseDeployed::class, [$runner, 'handleApplicationReleaseDeployed']);
    }

    protected function configureWorkflowEvents(): void
    {
        $activity = LogWorkflowActivity::class;

        Event::listen(WorkflowCreated::class, [$activity, 'handleWorkflowCreated']);
        Event::listen(WorkflowUpdated::class, [$activity, 'handleWorkflowUpdated']);
        Event::listen(WorkflowDeleted::class, [$activity, 'handleWorkflowDeleted']);
    }

    protected function configureSchedulerEvents(): void
    {
        $activity = LogSchedulerActivity::class;

        Event::listen(ScheduledJobCreated::class, [$activity, 'handleScheduledJobCreated']);
        Event::listen(ScheduledJobUpdated::class, [$activity, 'handleScheduledJobUpdated']);
        Event::listen(ScheduledJobDeleted::class, [$activity, 'handleScheduledJobDeleted']);
    }

    protected function configureAiEvents(): void
    {
        $activity = LogAiActivity::class;

        Event::listen(AiProviderCreated::class, [$activity, 'handleAiProviderCreated']);
        Event::listen(AiProviderUpdated::class, [$activity, 'handleAiProviderUpdated']);
        Event::listen(AiProviderDeleted::class, [$activity, 'handleAiProviderDeleted']);
        Event::listen(AiPromptCreated::class, [$activity, 'handleAiPromptCreated']);
        Event::listen(AiPromptUpdated::class, [$activity, 'handleAiPromptUpdated']);
        Event::listen(AiPromptDeleted::class, [$activity, 'handleAiPromptDeleted']);
    }

    protected function configureAnalyticsEvents(): void
    {
        $activity = LogAnalyticsActivity::class;

        Event::listen(AnalyticsEventRecorded::class, [$activity, 'handleAnalyticsEventRecorded']);
        Event::listen(AnalyticsDashboardCreated::class, [$activity, 'handleDashboardCreated']);
        Event::listen(AnalyticsDashboardUpdated::class, [$activity, 'handleDashboardUpdated']);
        Event::listen(AnalyticsDashboardDeleted::class, [$activity, 'handleDashboardDeleted']);
        Event::listen(AnalyticsWidgetCreated::class, [$activity, 'handleWidgetCreated']);
        Event::listen(AnalyticsWidgetUpdated::class, [$activity, 'handleWidgetUpdated']);
        Event::listen(AnalyticsWidgetDeleted::class, [$activity, 'handleWidgetDeleted']);
    }

    protected function configureAuditEvents(): void
    {
        $notifications = PrepareAuditNotifications::class;

        Event::listen(ActivityLogged::class, [$notifications, 'handleActivityLogged']);
        Event::listen(AuditCreated::class, [$notifications, 'handleAuditCreated']);
        Event::listen(ApiLogged::class, [$notifications, 'handleApiLogged']);
        Event::listen(SystemEventCreated::class, [$notifications, 'handleSystemEventCreated']);
        Event::listen(ErrorCaptured::class, [$notifications, 'handleErrorCaptured']);
    }

    protected function configurePolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Department::class, CompanyPolicy::class);
        Gate::policy(Team::class, CompanyPolicy::class);
        Gate::policy(CompanyLocation::class, CompanyPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerContact::class, CustomerPolicy::class);
        Gate::policy(CustomerApplication::class, CustomerPolicy::class);
        Gate::policy(Subscription::class, CustomerPolicy::class);
        Gate::policy(License::class, CustomerPolicy::class);
        Gate::policy(CustomerDocument::class, CustomerPolicy::class);
        Gate::policy(CustomerNote::class, CustomerPolicy::class);
        Gate::policy(CustomerTask::class, CustomerPolicy::class);
        Gate::policy(CustomerCommunication::class, CustomerPolicy::class);
        Gate::policy(CustomerAnalyticsSnapshot::class, CustomerPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(Content::class, ContentPolicy::class);
        Gate::policy(Integration::class, IntegrationPolicy::class);
        Gate::policy(Webhook::class, WebhookPolicy::class);
        Gate::policy(WebhookLog::class, WebhookPolicy::class);
        Gate::policy(SyncConfig::class, SyncPolicy::class);
        Gate::policy(SyncRun::class, SyncPolicy::class);
        Gate::policy(DataMapping::class, DataMappingPolicy::class);
        Gate::policy(QueueJobTrack::class, QueuePolicy::class);
        Gate::policy(MonitoringSnapshot::class, MonitoringPolicy::class);
        Gate::policy(MonitoringAlert::class, MonitoringPolicy::class);
        Gate::policy(MonitoringLog::class, MonitoringPolicy::class);
        Gate::policy(HealthCheck::class, MonitoringPolicy::class);
        Gate::policy(ServiceStatus::class, MonitoringPolicy::class);
        Gate::policy(SystemSetting::class, SettingsPolicy::class);
        Gate::policy(MediaFile::class, SettingsPolicy::class);
        Gate::policy(FileFolder::class, SettingsPolicy::class);
        Gate::policy(ActivityLog::class, AuditPolicy::class);
        Gate::policy(AuditLog::class, AuditPolicy::class);
        Gate::policy(ApiLog::class, AuditPolicy::class);
        Gate::policy(SystemEvent::class, AuditPolicy::class);
        Gate::policy(ErrorLog::class, AuditPolicy::class);
        Gate::policy(UserLoginHistory::class, AuditPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(ComplianceCase::class, ComplianceCasePolicy::class);
        Gate::policy(PrivacyRequest::class, PrivacyRequestPolicy::class);
        Gate::policy(UserConsent::class, ConsentPolicy::class);
        Gate::policy(ConsentType::class, ConsentPolicy::class);
        Gate::policy(DataBreach::class, DataBreachPolicy::class);
        Gate::policy(DpiaAssessment::class, DpiaPolicy::class);
        Gate::policy(RiskRegister::class, DpiaPolicy::class);
        Gate::policy(PolicyDocument::class, PolicyDocumentPolicy::class);
        Gate::policy(PlatformNotification::class, NotificationPolicy::class);
        Gate::policy(NotificationTemplate::class, NotificationTemplatePolicy::class);
        Gate::policy(NotificationChannel::class, NotificationChannelPolicy::class);
        Gate::policy(AutomationRule::class, AutomationRulePolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(ScheduledJob::class, ScheduledJobPolicy::class);
        Gate::policy(AiProvider::class, AiProviderPolicy::class);
        Gate::policy(AnalyticsSubject::class, AnalyticsPolicy::class);
        Gate::policy(AnalyticsEvent::class, AnalyticsPolicy::class);
        Gate::policy(AnalyticsDashboard::class, AnalyticsPolicy::class);
        Gate::policy(AnalyticsWidget::class, AnalyticsPolicy::class);
        Gate::policy(AnalyticsReport::class, AnalyticsPolicy::class);
        Gate::policy(AnalyticsReportRun::class, AnalyticsPolicy::class);
    }

    protected function configurePasswordResetUrls(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

            return $frontendUrl.'/auth/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
