<?php

namespace Tests\Feature\Users;

use App\Domains\Users\Enums\UserPermission;
use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
        ];

        $response = $this->postJson('/api/v1/users', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'grace@example.com')
            ->assertJsonPath('data.user.full_name', 'Grace Hopper')
            ->assertJsonMissingPath('data.user.password');

        $this->assertDatabaseHas('users', [
            'email' => 'grace@example.com',
            'created_by' => $this->admin->id,
        ]);
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

        $this->getJson('/api/v1/users/'.$user->uuid)
            ->assertOk()
            ->assertJsonPath('data.user.uuid', $user->uuid)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'activity_summary' => ['total', 'recent', 'last_activity_at'],
                ],
            ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/users/'.$user->uuid, [
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
    }

    public function test_admin_can_soft_delete_and_restore_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/'.$user->uuid)
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->postJson('/api/v1/users/'.$user->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.user.uuid', $user->uuid);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_only_force_delete_permission_can_permanently_delete(): void
    {
        $target = User::factory()->create();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->deleteJson('/api/v1/users/'.$target->uuid.'/force-delete')
            ->assertForbidden();

        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/'.$target->uuid.'/force-delete')
            ->assertOk()
            ->assertJsonPath('message', 'User permanently deleted.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        Sanctum::actingAs($this->admin);

        $this->deleteJson('/api/v1/users/'.$this->admin->uuid)
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
        Storage::disk('public')->assertExists($path);
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
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])->assertForbidden();
    }

    public function test_admin_role_cannot_force_delete_without_permission(): void
    {
        $adminRoleUser = User::factory()->create();
        $adminRoleUser->assignRole('admin');
        $this->assertFalse($adminRoleUser->can(UserPermission::FORCE_DELETE));

        $target = User::factory()->create();
        Sanctum::actingAs($adminRoleUser);

        $this->deleteJson('/api/v1/users/'.$target->uuid.'/force-delete')
            ->assertForbidden();
    }

    public function test_super_admin_role_has_force_delete_permission(): void
    {
        $role = Role::findByName('super-admin', 'web');
        $this->assertTrue($role->hasPermissionTo(UserPermission::FORCE_DELETE));
    }
}
