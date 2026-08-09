<?php

namespace Database\Seeders;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Enums\IntegrationType;
use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookSignatureAlgorithm;
use App\Domains\Integrations\Enums\WebhookStatus;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\Webhook;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local EasyCare API (k:\herd\easycare-api) connection for AMS Integration Hub.
 *
 * Default local base URL: http://127.0.0.1:8010
 * Override with EASYCARE_API_BASE_URL / EASYCARE_API_TOKEN / EASYCARE_AMS_WEBHOOK_SECRET.
 */
class EasyCareCompanySeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->where('email', 'admin@ams.test')->first()
            ?? User::query()->orderBy('id')->first();

        $company = $this->seedCompany($actor);
        $integration = $this->seedIntegration($company, $actor);
        $this->seedApplication($company, $integration, $actor, ApplicationPlatform::Web, 'easycare-web');
        $this->seedIncomingWebhook($company, $integration, $actor);
        $this->seedOutgoingWebhook($company, $integration, $actor);
    }

    private function seedCompany(?User $actor): Company
    {
        $company = Company::query()->firstOrCreate(
            ['company_name' => 'EasyCare'],
            [
                'legal_name' => 'EasyCare',
                'email' => 'admin@easycare.test',
                'website' => 'http://127.0.0.1:8010',
                'country' => 'GB',
                'timezone' => 'UTC',
                'language' => 'en',
                'currency' => 'GBP',
                'status' => CompanyStatus::Active,
                'settings' => [
                    'api_base_url' => rtrim((string) env('EASYCARE_API_BASE_URL', 'http://127.0.0.1:8010'), '/'),
                    'swagger_url' => rtrim((string) env('EASYCARE_API_BASE_URL', 'http://127.0.0.1:8010'), '/').'/api/documentation',
                    'health_url' => rtrim((string) env('EASYCARE_API_BASE_URL', 'http://127.0.0.1:8010'), '/').'/health',
                ],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        if ($actor && ! $company->users()->where('users.id', $actor->id)->exists()) {
            $company->users()->attach($actor->id, [
                'is_primary' => true,
                'status' => 'active',
            ]);
        }

        return $company;
    }

    private function seedIntegration(Company $company, ?User $actor): Integration
    {
        $baseUrl = rtrim((string) env('EASYCARE_API_BASE_URL', 'http://127.0.0.1:8010'), '/').'/';
        $token = (string) env('EASYCARE_API_TOKEN', 'SEED_PLACEHOLDER_TOKEN');

        $integration = Integration::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'easycare-api',
            ],
            [
                'name' => 'EasyCare API',
                'description' => 'Local EasyCare healthcare REST API (Sanctum bearer). Default http://127.0.0.1:8010.',
                'type' => IntegrationType::RestApi,
                'status' => IntegrationStatus::Active,
                'authentication_type' => IntegrationAuthenticationType::BearerToken,
                'base_url' => $baseUrl,
                'api_version' => 'v1',
                'timeout' => 30,
                'retry_attempts' => 3,
                'health_check_path' => '/api/v1/health',
                'credentials' => [
                    'bearer_token' => $token,
                ],
                'health_status' => IntegrationHealthStatus::Unknown,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        // Keep local URL / token in sync on re-seed without wiping other config.
        $integration->forceFill([
            'base_url' => $baseUrl,
            'health_check_path' => '/api/v1/health',
            'status' => IntegrationStatus::Active,
            'authentication_type' => IntegrationAuthenticationType::BearerToken,
            'credentials' => array_merge($integration->credentials ?? [], [
                'bearer_token' => $token !== '' ? $token : ($integration->credentials['bearer_token'] ?? 'SEED_PLACEHOLDER_TOKEN'),
            ]),
            'updated_by' => $actor?->id,
        ])->save();

        return $integration->fresh();
    }

    private function seedApplication(
        Company $company,
        Integration $integration,
        ?User $actor,
        ApplicationPlatform $platform,
        string $slug,
    ): Application {
        return Application::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => $slug,
            ],
            [
                'integration_id' => $integration->id,
                'name' => 'EasyCare',
                'description' => 'EasyCare healthcare platform (local easycare-api).',
                'platform' => $platform,
                'category' => ApplicationCategory::Health,
                'current_version' => '1.0.0',
                'minimum_supported_version' => '1.0.0',
                'status' => ApplicationStatus::Active,
                'visibility' => ApplicationVisibility::Internal,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );
    }

    private function seedIncomingWebhook(Company $company, Integration $integration, ?User $actor): Webhook
    {
        $secret = (string) env('EASYCARE_AMS_WEBHOOK_SECRET', env('AMS_WEBHOOK_SECRET', 'easycare-ams-secret'));

        $webhook = Webhook::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'easycare',
            ],
            [
                'integration_id' => $integration->id,
                'name' => 'EasyCare Incoming',
                'description' => 'Receives signed EasyCare webhooks (X-EasyCare-Signature).',
                'direction' => WebhookDirection::Incoming,
                'status' => WebhookStatus::Active,
                'secret' => $secret,
                'signature_algorithm' => WebhookSignatureAlgorithm::HmacSha256,
                'signature_header' => 'X-EasyCare-Signature',
                'subscribed_events' => [
                    'user.created',
                    'user.updated',
                    'patient.created',
                    'patient.updated',
                    'blood_sugar.created',
                    'appointment.created',
                    'medicine.updated',
                    'easycare.test',
                    'support.sms.received',
                    'support.message.received',
                    'support.ticket.created',
                ],
                'timeout' => 30,
                'retry_attempts' => 3,
                'retry_delay_seconds' => 60,
                'verify_ssl' => false,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        $webhook->forceFill([
            'integration_id' => $integration->id,
            'direction' => WebhookDirection::Incoming,
            'status' => WebhookStatus::Active,
            'secret' => $secret,
            'signature_algorithm' => WebhookSignatureAlgorithm::HmacSha256,
            'signature_header' => 'X-EasyCare-Signature',
            'subscribed_events' => [
                'user.created',
                'user.updated',
                'patient.created',
                'patient.updated',
                'blood_sugar.created',
                'appointment.created',
                'medicine.updated',
                'easycare.test',
                'support.sms.received',
                'support.message.received',
                'support.ticket.created',
            ],
            'updated_by' => $actor?->id,
        ])->save();

        $this->command?->info(
            'EasyCare incoming webhook URL: '
            .rtrim((string) config('app.url'), '/')
            .'/api/v1/webhooks/incoming/easycare'
        );

        return $webhook->fresh();
    }

    private function seedOutgoingWebhook(Company $company, Integration $integration, ?User $actor): Webhook
    {
        $secret = (string) env('EASYCARE_AMS_WEBHOOK_SECRET', env('AMS_WEBHOOK_SECRET', 'easycare-ams-secret'));
        $replyUrl = rtrim((string) env(
            'EASYCARE_SUPPORT_REPLY_URL',
            rtrim((string) env('EASYCARE_API_BASE_URL', 'http://127.0.0.1:8010'), '/').'/api/v1/ams/support-replies'
        ), '/');

        // Normalize if env base already includes path accidentally.
        if (! str_contains($replyUrl, '/api/v1/ams/support-replies')) {
            $replyUrl = rtrim($replyUrl, '/').'/api/v1/ams/support-replies';
        }

        $webhook = Webhook::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'easycare-replies',
            ],
            [
                'integration_id' => $integration->id,
                'name' => 'EasyCare Support Replies',
                'description' => 'Pushes AMS public agent replies to EasyCare (support.reply.sent / support.sms.sent).',
                'direction' => WebhookDirection::Outgoing,
                'url' => $replyUrl,
                'status' => WebhookStatus::Active,
                'secret' => $secret,
                'signature_algorithm' => WebhookSignatureAlgorithm::HmacSha256,
                'signature_header' => 'X-AMS-Signature',
                'subscribed_events' => [
                    'support.reply.sent',
                    'support.sms.sent',
                    'support.ticket.updated',
                ],
                'timeout' => 30,
                'retry_attempts' => 3,
                'retry_delay_seconds' => 60,
                'verify_ssl' => false,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        $webhook->forceFill([
            'integration_id' => $integration->id,
            'direction' => WebhookDirection::Outgoing,
            'url' => $replyUrl,
            'status' => WebhookStatus::Active,
            'secret' => $secret,
            'signature_algorithm' => WebhookSignatureAlgorithm::HmacSha256,
            'signature_header' => 'X-AMS-Signature',
            'subscribed_events' => [
                'support.reply.sent',
                'support.sms.sent',
                'support.ticket.updated',
            ],
            'updated_by' => $actor?->id,
        ])->save();

        $this->command?->info('EasyCare outgoing reply URL: '.$replyUrl);

        return $webhook->fresh();
    }
}
