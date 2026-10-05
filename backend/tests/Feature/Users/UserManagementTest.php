<?php

namespace Tests\Feature\Users;

use App\Domains\Authentication\Services\TotpService;
use App\Domains\Companies\Models\Company;
use App\Domains\Users\Enums\UserPermission;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Notifications\UserPasswordSetupNotification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_list_users_with_pagination_and_statistics(): void
    {
        User::factory()->count(3)->create();
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/users?per_page=2&sort_by=full_name&sort_dir=asc');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'users' => [
                        'items',
                        'meta' => ['current_page', 'per_page', 'total'],
                        'links',
                    ],
                    'statistics' => ['total', 'active', 'inactive', 'suspended', 'pending', 'trashed'],
                ],
            ]);

        $this->assertSame(2, count($response->json('data.users.items')));
    }

    public function test_admin_can_search_and_filter_users(): void
    {
        User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'status' => UserStatus::Active,
        ]);
        User::factory()->inactive()->create([
            'email' => 'hidden@example.com',
        ]);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users?search=Ada&status=active')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.users.meta.total', 1)
            ->assertJsonPath('data.users.items.0.email', 'ada@example.com');
    }

    public function test_admin_can_create_user(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'phone' => '+15551234567',
            'timezone' => 'UTC',
            'language' => 'en',
            'roles' => ['support-agent'],
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'grace@example.com')
            ->assertJsonPath('data.user.full_name', 'Grace Hopper')
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseHas('users', [
            'email' => 'grace@example.com',
            'phone' => '+15551234567',
            'status' => 'inactive',
            'invitation_status' => 'pending',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $created = User::query()->where('email', 'grace@example.com')->first();
        $this->assertNotNull($created);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'users',
            'action' => 'created',
            'user_id' => $this->admin->id,
            'subject_id' => $created->id,
        ]);
    }

    public function test_create_user_normalizes_formatted_phone_to_e164(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Phone',
            'last_name' => 'Stored',
            'email' => 'phone.stored@example.com',
            'phone' => '+1 (555) 987-6543',
            'roles' => ['support-agent'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.phone', '+15559876543');

        $this->assertDatabaseHas('users', [
            'email' => 'phone.stored@example.com',
            'phone' => '+15559876543',
        ]);
    }

    public function test_create_user_rejects_phone_without_country_code(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Phone',
            'last_name' => 'Invalid',
            'email' => 'phone.invalid@example.com',
            'phone' => '5551234567',
            'roles' => ['support-agent'],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['phone']]);
    }

    public function test_admin_can_create_user_with_role_assignment(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'first_name' => 'Alan',
            'last_name' => 'Turing',
            'email' => 'alan@example.com',
            'roles' => ['support-agent'],
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'alan@example.com')
            ->assertJsonPath('data.user.roles.0.name', 'support-agent');

        $created = User::query()->where('email', 'alan@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('support-agent'));
    }

    public function test_create_user_validates_unique_email_and_password_rules(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'roles' => ['support-agent'],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation Failed')
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_admin_can_view_user_with_activity_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users/' . $user->uuid)
            ->assertOk()
            ->assertJsonPath('data.user.uuid', $user->uuid)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['created_at', 'updated_at', 'created_by', 'updated_by'],
                    'activity_summary' => ['total', 'recent', 'last_activity_at'],
                ],
            ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/users/' . $user->uuid, [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'new@example.com',
            'status' => 'suspended',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'new@example.com')
            ->assertJsonPath('data.user.status', 'suspended')
            ->assertJsonPath('data.user.is_active', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
            'status' => 'suspended',
            'updated_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'users',
            'action' => 'updated',
            'user_id' => $this->admin->id,
            'subject_id' => $user->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'users',
            'action' => 'status_changed',
            'user_id' => $this->admin->id,
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_soft_delete_and_restore_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/' . $user->uuid)
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_by' => $this->admin->id,
        ]);

        $this->getJson('/api/v1/users?trashed=only')
            ->assertOk()
            ->assertJsonPath('data.users.items.0.deleted_by.full_name', $this->admin->full_name)
            ->assertJsonPath('data.users.items.0.deleted_by_name', $this->admin->full_name);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'users',
            'action' => 'deleted',
            'user_id' => $this->admin->id,
            'subject_id' => $user->id,
        ]);

        $this->postJson('/api/v1/users/' . $user->uuid . '/restore')
            ->assertOk()
            ->assertJsonPath('data.user.uuid', $user->uuid);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
            'updated_by' => $this->admin->id,
            'deleted_by' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'users',
            'action' => 'restored',
            'user_id' => $this->admin->id,
            'subject_id' => $user->id,
        ]);
    }

    public function test_only_force_delete_permission_can_permanently_delete(): void
    {
        $target = User::factory()->create();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->shareCompany($manager, $target);
        Sanctum::actingAs($manager);

        $this->deleteJson('/api/v1/users/' . $target->uuid . '/force-delete')
            ->assertForbidden();

        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/' . $target->uuid . '/force-delete')
            ->assertOk()
            ->assertJsonPath('message', 'User permanently deleted.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/' . $this->admin->uuid)
            ->assertForbidden();
    }

    public function test_user_can_view_and_update_own_profile(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', $this->admin->email);

        $this->putJson('/api/v1/users/profile', [
            'first_name' => 'Sys',
            'last_name' => 'Admin',
            'phone' => '+15559876543',
            'timezone' => 'America/New_York',
            'language' => 'en',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.first_name', 'Sys')
            ->assertJsonPath('data.user.phone', '+15559876543');
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->post('/api/v1/users/avatar', [
            'avatar' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user' => ['avatar', 'avatar_url']]]);

        $path = $response->json('data.user.avatar');
        $this->assertIsString($path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_create_user_with_roles_requires_assign_roles_permission(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo([UserPermission::CREATE, UserPermission::VIEW]);
        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/users', [
            'first_name' => 'No',
            'last_name' => 'Roles',
            'email' => 'noroles.assign@example.com',
            'roles' => ['support-agent'],
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('users', ['email' => 'noroles.assign@example.com']);
    }

    public function test_create_user_without_roles_does_not_require_assign_roles_permission(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo([UserPermission::CREATE, UserPermission::VIEW]);
        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/users', [
            'first_name' => 'No',
            'last_name' => 'Roles',
            'email' => 'noroles.create@example.com',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'noroles.create@example.com');
    }

    public function test_create_user_rejects_an_empty_role_list(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'No',
            'last_name' => 'Role',
            'email' => 'empty.role@example.com',
            'roles' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.roles.0', 'The role field is required.');

        $this->assertDatabaseMissing('users', ['email' => 'empty.role@example.com']);
    }

    public function test_manager_cannot_assign_roles_when_updating_user(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $target = User::factory()->create();
        $this->shareCompany($manager, $target);
        Sanctum::actingAs($manager);

        $this->putJson('/api/v1/users/'.$target->uuid, [
            'first_name' => 'Updated',
            'roles' => ['support-agent'],
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertFalse($target->fresh()->hasRole('support-agent'));
        $this->assertSame($target->first_name, $target->fresh()->first_name);
    }

    public function test_manager_can_update_user_without_roles(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $target = User::factory()->create(['first_name' => 'Old']);
        $this->shareCompany($manager, $target);
        Sanctum::actingAs($manager);

        $this->putJson('/api/v1/users/'.$target->uuid, [
            'first_name' => 'Updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.first_name', 'Updated');
    }

    public function test_manager_without_create_permission_cannot_create_users(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/users', [
            'first_name' => 'No',
            'last_name' => 'Access',
            'email' => 'noaccess@example.com',
            'roles' => ['support-agent'],
        ])->assertForbidden();
    }

    public function test_admin_role_cannot_force_delete_without_permission(): void
    {
        $adminRoleUser = User::factory()->create();
        $adminRoleUser->assignRole('admin');
        $this->assertFalse($adminRoleUser->can(UserPermission::FORCE_DELETE));

        $target = User::factory()->create();
        $this->shareCompany($adminRoleUser, $target);
        Sanctum::actingAs($adminRoleUser);

        $this->deleteJson('/api/v1/users/' . $target->uuid . '/force-delete')
            ->assertForbidden();
    }

    public function test_super_admin_role_has_force_delete_permission(): void
    {
        $role = Role::findByName('super-admin', 'web');
        $this->assertTrue($role->hasPermissionTo(UserPermission::FORCE_DELETE));
    }

    public function test_create_user_sends_invitation_without_a_password(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Invite',
            'last_name' => 'User',
            'email' => 'invite@example.com',
            'roles' => ['support-agent'],
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['password']]);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Invite',
            'last_name' => 'User',
            'email' => 'invite@example.com',
            'roles' => ['support-agent', 'admin'],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.roles.0', 'A user can have only one role.');

        $this->postJson('/api/v1/users', [
            'first_name' => 'Invite',
            'last_name' => 'User',
            'email' => 'invite@example.com',
            'roles' => ['support-agent'],
        ])->assertCreated()
            ->assertJsonPath('data.user.lifecycle_status', 'pending_invitation')
            ->assertJsonPath('data.user.status', 'inactive')
            ->assertJsonMissingPath('data.user.password');

        $created = User::query()->where('email', 'invite@example.com')->firstOrFail();
        $this->assertFalse($created->isAccountActive());

        Notification::assertSentTo($created, UserPasswordSetupNotification::class, function (UserPasswordSetupNotification $notification) use ($created): bool {
            $mail = $notification->toMail($created);
            $rendered = implode(' ', [
                ...$mail->introLines,
                ...$mail->outroLines,
                (string) $mail->actionUrl,
            ]);

            return str_contains((string) $mail->actionUrl, 'setup=1')
                && str_contains((string) $mail->actionUrl, '/auth/reset-password')
                && ! str_contains($rendered, 'Password@123');
        });
    }

    public function test_accepting_invitation_activates_the_account(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Ready',
            'last_name' => 'User',
            'email' => 'ready@example.com',
            'roles' => ['support-agent'],
        ])->assertCreated();

        $created = User::query()->where('email', 'ready@example.com')->firstOrFail();

        $this->putJson('/api/v1/users/'.$created->uuid, [
            'status' => 'active',
        ])->assertStatus(422);

        $token = Password::broker()->createToken($created);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $created->email,
            'token' => $token,
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])->assertOk();

        $created->refresh();
        $this->assertTrue($created->isAccountActive());
        $this->assertSame('accepted', $created->invitation_status->value);
        $this->assertSame('active', $created->status->value);
    }

    public function test_user_timezone_defaults_from_company(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Locale Co',
            'status' => 'active',
            'country' => 'US',
            'timezone' => 'America/Chicago',
            'language' => 'fr',
            'currency' => 'USD',
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Locale',
            'last_name' => 'User',
            'email' => 'locale@example.com',
            'roles' => ['support-agent'],
            'company_id' => $company->uuid,
        ])->assertCreated()
            ->assertJsonPath('data.user.timezone', 'America/Chicago')
            ->assertJsonPath('data.user.language', 'fr');
    }

    public function test_pending_invitation_is_filterable_and_excluded_from_active(): void
    {
        Sanctum::actingAs($this->admin);
        User::factory()->create(['email' => 'already-active@example.com']);

        $this->postJson('/api/v1/users', [
            'first_name' => 'Pending',
            'last_name' => 'Invite',
            'email' => 'pending.invite@example.com',
            'roles' => ['support-agent'],
        ])->assertCreated();

        $this->getJson('/api/v1/users?status=pending_invitation')
            ->assertOk()
            ->assertJsonPath('data.users.meta.total', 1)
            ->assertJsonPath('data.users.items.0.email', 'pending.invite@example.com');

        $this->getJson('/api/v1/users?status=active')
            ->assertOk()
            ->assertJsonMissing(['email' => 'pending.invite@example.com']);
    }

    public function test_protected_system_administrator_cannot_be_removed_or_deactivated(): void
    {
        $system = User::factory()->create([
            'email' => 'system.admin@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
            'is_protected' => true,
        ]);
        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/'.$system->uuid)->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $system->id, 'deleted_at' => null]);

        $this->putJson('/api/v1/users/'.$system->uuid, [
            'status' => 'suspended',
        ])->assertStatus(422);

        $system->refresh();
        $this->assertSame('active', $system->status->value);
        $this->assertTrue($system->isAccountActive());

        Sanctum::actingAs($system);
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'Password@123',
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('Password@123', $system->fresh()->password));
    }

    public function test_two_factor_login_requires_a_one_time_code(): void
    {
        $user = User::factory()->create([
            'email' => 'mfa@example.com',
            'password' => Hash::make('Password@123'),
        ]);
        Sanctum::actingAs($user);

        $secret = $this->postJson('/api/v1/users/profile/two-factor')
            ->assertOk()
            ->json('data.secret');

        $this->postJson('/api/v1/users/profile/two-factor/confirm', [
            'code' => app(TotpService::class)->currentCode($secret),
        ])->assertOk()
            ->assertJsonPath('data.user.two_factor_enabled', true);

        $this->flushSession();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'mfa@example.com',
            'password' => 'Password@123',
        ])->assertOk()
            ->assertJsonPath('data.mfa_required', true)
            ->assertJsonMissingPath('data.token');

        $this->postJson('/api/v1/auth/two-factor', [
            'challenge' => $login->json('data.challenge'),
            'code' => app(TotpService::class)->currentCode($secret),
        ])->assertOk()
            ->assertJsonPath('data.mfa_required', false)
            ->assertJsonStructure(['data' => ['token']]);
    }

    private function shareCompany(User ...$users): void
    {
        $company = Company::query()->create([
            'company_name' => 'Shared Co '.uniqid(),
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        foreach ($users as $user) {
            $company->users()->attach($user->id, ['is_primary' => true, 'status' => 'active']);
        }
    }
}
