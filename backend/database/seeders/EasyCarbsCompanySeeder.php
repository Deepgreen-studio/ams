<?php

namespace Database\Seeders;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationEnvironmentHealthStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentType;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Integrations\Enums\DataMappingDirection;
use App\Domains\Integrations\Enums\DataMappingStatus;
use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Enums\IntegrationType;
use App\Domains\Integrations\Models\DataMapping;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Services\SupportTicketService;
use App\Models\User;
use Illuminate\Database\Seeder;

class EasyCarbsCompanySeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->where('email', 'admin@ams.test')->first()
            ?? User::query()->orderBy('id')->first();

        $company = $this->seedCompany($actor);
        $integration = $this->seedIntegration($company, $actor);
        $androidApp = $this->seedApplication($company, $integration, $actor, ApplicationPlatform::Android, 'easycarbs-android', '1.0.0');
        $iosApp = $this->seedApplication($company, $integration, $actor, ApplicationPlatform::Ios, 'easycarbs-ios', '1.1.0');
        $this->seedEnvironments($androidApp, $actor);
        $this->seedEnvironments($iosApp, $actor);
        $this->seedCustomer($company, $actor);
        $this->seedMapping($company, $integration, $actor);
        $this->seedDemoRequests($company, $androidApp, $actor);
    }

    private function seedCompany(?User $actor): Company
    {
        $company = Company::query()->firstOrCreate(
            ['company_name' => 'EasyCarbs'],
            [
                'legal_name' => 'EasyCarbs',
                'email' => 'admin@easycarbs.com',
                'website' => 'https://easycarbs.com',
                'country' => 'GB',
                'timezone' => 'Europe/London',
                'language' => 'en',
                'currency' => 'GBP',
                'status' => CompanyStatus::Active,
                'settings' => [
                    'app_package' => 'com.easy.carbs',
                    'ios_bundle_id' => 'com.easy.carbs',
                    'api_base_url' => 'https://panel.easycarbs.com/',
                    'admin_portal_url' => 'https://panel.easycarbs.com/admin',
                    'privacy_policy_url' => 'https://easycarbs.com/privacy-policy/',
                    'play_store_url' => 'https://play.google.com/store/apps/details?id=com.easy.carbs',
                    'app_store_url' => 'https://apps.apple.com/us/app/easycarbs/id6747334755',
                    'android_version' => '1.0.0',
                    'android_build' => '6',
                    'ios_version' => '1.1.0',
                    'ios_build' => '1',
                    'last_release_date' => '2026-07-15',
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
        return Integration::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'easycarbs-api',
            ],
            [
                'name' => 'EasyCarbs API',
                'description' => 'EasyCarbs panel REST API (Sanctum bearer token). See docs/easycarbs/API-Documentation.md.',
                'type' => IntegrationType::RestApi,
                'status' => IntegrationStatus::Active,
                'authentication_type' => IntegrationAuthenticationType::BearerToken,
                'base_url' => 'https://panel.easycarbs.com/',
                'api_version' => 'v1',
                'timeout' => 30,
                'retry_attempts' => 3,
                'health_check_path' => '/api/app-name',
                'credentials' => [
                    'bearer_token' => 'SEED_PLACEHOLDER_TOKEN',
                ],
                'health_status' => IntegrationHealthStatus::Unknown,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );
    }

    private function seedApplication(
        Company $company,
        Integration $integration,
        ?User $actor,
        ApplicationPlatform $platform,
        string $slug,
        string $version,
    ): Application {
        return Application::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => $slug,
            ],
            [
                'integration_id' => $integration->id,
                'name' => 'EasyCarbs',
                'description' => 'EasyCarbs health and carb management mobile application ('.$platform->value.').',
                'platform' => $platform,
                'category' => ApplicationCategory::Health,
                'current_version' => $version,
                'minimum_supported_version' => '1.0.0',
                'status' => ApplicationStatus::Active,
                'visibility' => ApplicationVisibility::Public,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );
    }

    private function seedEnvironments(Application $application, ?User $actor): void
    {
        ApplicationEnvironment::query()->firstOrCreate(
            [
                'application_id' => $application->id,
                'type' => ApplicationEnvironmentType::Production,
            ],
            [
                'name' => 'Production',
                'slug' => 'production',
                'api_url' => 'https://panel.easycarbs.com/',
                'web_url' => 'https://easycarbs.com',
                'status' => ApplicationEnvironmentStatus::Active,
                'health_status' => ApplicationEnvironmentHealthStatus::Unknown,
                'is_current' => true,
                'variables' => [
                    'APP_ENV' => 'production',
                    'PACKAGE' => 'com.easy.carbs',
                ],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        ApplicationEnvironment::query()->firstOrCreate(
            [
                'application_id' => $application->id,
                'type' => ApplicationEnvironmentType::Staging,
            ],
            [
                'name' => 'Staging',
                'slug' => 'staging',
                'api_url' => 'http://13.41.111.205:8000/',
                'web_url' => null,
                'status' => ApplicationEnvironmentStatus::Active,
                'health_status' => ApplicationEnvironmentHealthStatus::Unknown,
                'is_current' => false,
                'variables' => [
                    'APP_ENV' => 'staging',
                ],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );
    }

    private function seedCustomer(Company $company, ?User $actor): void
    {
        Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'mrdavid@gmail.com',
            ],
            [
                'customer_type' => CustomerType::Individual,
                'first_name' => 'David',
                'last_name' => 'Test',
                'phone' => null,
                'country' => 'GB',
                'timezone' => 'Europe/London',
                'language' => 'en',
                'status' => CustomerStatus::Active,
                'notes' => 'EasyCarbs DTAC assessment test account (app login). Password stored out-of-band.',
                'industry' => 'Health',
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'admin@example.com',
            ],
            [
                'customer_type' => CustomerType::Business,
                'company_name' => 'EasyCarbs Admin',
                'first_name' => null,
                'last_name' => null,
                'country' => 'GB',
                'timezone' => 'Europe/London',
                'language' => 'en',
                'status' => CustomerStatus::Active,
                'notes' => 'EasyCarbs admin panel assessment account. Password stored out-of-band.',
                'industry' => 'Health',
                'website' => 'https://panel.easycarbs.com/admin',
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );
    }

    private function seedMapping(Company $company, Integration $integration, ?User $actor): void
    {
        $mapping = DataMapping::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'easycarbs-customers',
            ],
            [
                'integration_id' => $integration->id,
                'name' => 'EasyCarbs Customers',
                'description' => 'Inbound mapping from EasyCarbs payloads to AMS Users/Health fields.',
                'source_entity' => 'EasyCarbs',
                'target_entity' => 'Users',
                'direction' => DataMappingDirection::Inbound,
                'status' => DataMappingStatus::Active,
                'is_active' => true,
                'sample_payload' => [
                    'customer_name' => 'Ada Lovelace',
                    'weight' => '62.5',
                ],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]
        );

        $fields = [
            [
                'external_field' => 'customer_name',
                'internal_field' => 'Users.first_name',
                'transform_type' => 'split_first',
                'transform_config' => ['delimiter' => ' '],
                'is_required' => true,
                'sort_order' => 0,
                'is_enabled' => true,
            ],
            [
                'external_field' => 'weight',
                'internal_field' => 'Health.weight',
                'transform_type' => 'cast_float',
                'transform_config' => null,
                'is_required' => true,
                'sort_order' => 1,
                'is_enabled' => true,
            ],
            [
                'external_field' => 'email',
                'internal_field' => 'Users.email',
                'transform_type' => 'trim',
                'transform_config' => null,
                'is_required' => false,
                'sort_order' => 2,
                'is_enabled' => true,
            ],
            [
                'external_field' => 'phone',
                'internal_field' => 'Users.phone',
                'transform_type' => 'trim',
                'transform_config' => null,
                'is_required' => false,
                'sort_order' => 3,
                'is_enabled' => true,
            ],
        ];

        foreach ($fields as $field) {
            $mapping->fields()->updateOrCreate(
                [
                    'external_field' => $field['external_field'],
                    'internal_field' => $field['internal_field'],
                ],
                $field
            );
        }
    }

    private function seedDemoRequests(Company $company, Application $application, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        $customer = Customer::query()
            ->where('company_id', $company->id)
            ->where('email', 'mrdavid@gmail.com')
            ->first();

        if (! $customer) {
            return;
        }

        $service = app(SupportTicketService::class);

        // 1) Personal-data request → Support intake → auto-route to Compliance.
        $healthSubject = 'Please remove my health information from my account.';
        $healthExists = SupportTicket::query()
            ->where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->where('subject', $healthSubject)
            ->whereNotNull('privacy_request_id')
            ->exists();

        if (! $healthExists) {
            $service->create([
                'company_id' => $company->uuid,
                'customer_id' => $customer->uuid,
                'application_id' => $application->uuid,
                'subject' => $healthSubject,
                'description' => $healthSubject."\n\n"
                    .'Demo EasyCarbs workflow: involves personal health data. '
                    .'Support cannot fulfil erasure — auto-route to Compliance privacy request.',
                'priority' => SupportTicketPriority::High->value,
                'category' => SupportTicketCategory::CustomerSupport->value,
                'source' => SupportTicketSource::Portal->value,
                'involves_personal_data' => true,
                'assigned_to' => $actor->uuid,
            ], $actor);
        }

        // 2) Operational account disable → remains in Support.
        $disableSubject = 'I would like to temporarily disable my account.';
        $disableExists = SupportTicket::query()
            ->where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->where('subject', $disableSubject)
            ->exists();

        if (! $disableExists) {
            $service->create([
                'company_id' => $company->uuid,
                'customer_id' => $customer->uuid,
                'application_id' => $application->uuid,
                'subject' => $disableSubject,
                'description' => $disableSubject."\n\n"
                    .'Demo EasyCarbs workflow: operational Support request (no Compliance escalation).',
                'priority' => SupportTicketPriority::Medium->value,
                'category' => SupportTicketCategory::CustomerSupport->value,
                'source' => SupportTicketSource::Portal->value,
                'involves_personal_data' => false,
                'assigned_to' => $actor->uuid,
            ], $actor);
        }

        $customer->status = CustomerStatus::Suspended;
        $customer->notes = '[SUPPORT] Account temporarily disabled per EasyCarbs demo Support request.';
        $customer->updated_by = $actor->id;
        $customer->save();
    }
}
