<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\UserConsent;
use App\Models\User;
use Database\Seeders\ConsentTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ConsentTypeSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'consent-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Consent Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_consents(): void
    {
        $this->getJson('/api/v1/compliance/consents')->assertUnauthorized();
    }

    public function test_platform_consent_types_are_seeded(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/compliance/consents/types?all=1')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('consent_types', ['code' => 'marketing', 'company_id' => null]);
        $this->assertDatabaseHas('consent_types', ['code' => 'cookie', 'company_id' => null]);
        $this->assertSame(6, ConsentType::query()->whereNull('company_id')->count());
    }

    public function test_admin_can_grant_and_list_consent(): void
    {
        Sanctum::actingAs($this->admin);

        $type = ConsentType::query()->where('code', 'email')->whereNull('company_id')->firstOrFail();

        $create = $this->postJson('/api/v1/compliance/consents', [
            'company_id' => $this->company->uuid,
            'consent_type_id' => $type->uuid,
            'subject_email' => 'subject@example.com',
            'subject_name' => 'Consent Subject',
            'granted' => true,
            'source' => 'admin',
            'device' => 'Desktop Chrome',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.consent.status', 'granted')
            ->assertJsonPath('data.consent.granted', true)
            ->assertJsonPath('data.consent.subject_email', 'subject@example.com')
            ->assertJsonPath('data.consent.consent_version', '1.0');

        $this->assertNotNull($create->json('data.consent.ip_address'));

        $uuid = $create->json('data.consent.uuid');

        $this->getJson('/api/v1/compliance/consents?search=subject@example.com')
            ->assertOk()
            ->assertJsonPath('data.consents.meta.total', 1);

        $this->getJson('/api/v1/compliance/consents/'.$uuid.'/timeline')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_consent_can_be_withdrawn(): void
    {
        Sanctum::actingAs($this->admin);

        $type = ConsentType::query()->where('code', 'marketing')->whereNull('company_id')->firstOrFail();

        $consent = UserConsent::factory()->forType($type)->forCompany($this->company)->create([
            'subject_email' => 'withdraw@example.com',
            'status' => 'granted',
            'granted' => true,
            'consented_at' => now(),
        ]);

        $this->postJson('/api/v1/compliance/consents/'.$consent->uuid.'/withdraw', [
            'notes' => 'User requested opt-out',
            'source' => 'preference_center',
        ])
            ->assertOk()
            ->assertJsonPath('data.consent.status', 'withdrawn')
            ->assertJsonPath('data.consent.granted', false);

        $this->assertNotNull($consent->fresh()->withdrawn_at);
    }

    public function test_preference_center_save_and_load(): void
    {
        Sanctum::actingAs($this->admin);

        $emailType = ConsentType::query()->where('code', 'email')->whereNull('company_id')->firstOrFail();
        $smsType = ConsentType::query()->where('code', 'sms')->whereNull('company_id')->firstOrFail();

        $this->postJson('/api/v1/compliance/consents/preferences', [
            'company_id' => $this->company->uuid,
            'subject_email' => 'prefs@example.com',
            'subject_name' => 'Prefs User',
            'source' => 'preference_center',
            'preferences' => [
                ['consent_type_id' => $emailType->uuid, 'granted' => true],
                ['consent_type_id' => $smsType->uuid, 'granted' => false],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $center = $this->getJson('/api/v1/compliance/consents/preferences?'.http_build_query([
            'company_id' => $this->company->uuid,
            'subject_email' => 'prefs@example.com',
        ]));

        $center->assertOk();
        $preferences = collect($center->json('data.preferences'));
        $emailPref = $preferences->first(fn ($row) => ($row['consent_type']['code'] ?? null) === 'email');
        $smsPref = $preferences->first(fn ($row) => ($row['consent_type']['code'] ?? null) === 'sms');

        $this->assertTrue($emailPref['granted']);
        $this->assertFalse($smsPref['granted']);
        $this->assertSame('withdrawn', $smsPref['status']);
    }

    public function test_consent_dashboard_and_audit_history(): void
    {
        Sanctum::actingAs($this->admin);

        $type = ConsentType::query()->where('code', 'analytics')->whereNull('company_id')->firstOrFail();

        $this->postJson('/api/v1/compliance/consents', [
            'company_id' => $this->company->uuid,
            'consent_type_id' => $type->code,
            'subject_email' => 'audit@example.com',
            'granted' => true,
            'source' => 'web',
        ])->assertCreated();

        $this->getJson('/api/v1/compliance/consents/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['total', 'granted', 'withdrawn'],
                    'recent',
                    'types',
                ],
            ]);

        $this->getJson('/api/v1/compliance/consents/history?company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('data.history.meta.total', 1);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/compliance/consents')->assertForbidden();
        $this->getJson('/api/v1/compliance/consents/dashboard')->assertForbidden();
    }
}
