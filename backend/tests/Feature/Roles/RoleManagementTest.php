<?php

namespace Tests\Feature\Roles;

use App\Domains\Roles\Enums\RolePermission;
use App\Domains\Roles\Models\Permission;
use App\Domains\Roles\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'rbac-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_guest_cannot_list_roles(): void
    {
        $this->getJson('/api/v1/roles')
            ->assertUnauthorized();
    }

    public function test_default_roles_and_permission_groups_are_seeded(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseHas('roles', ['name' => 'company-admin']);
        $this->assertDatabaseHas('roles', ['name' => 'read-only-user']);
        $this->assertDatabaseHas('permission_groups', ['slug' => 'users']);
        $this->assertDatabaseHas('permissions', ['name' => 'applications.view']);
        $this->assertTrue(Role::findByName('super-admin', 'web')->hasPermissionTo(RolePermission::ASSIGN_USERS));
    }

    public function test_admin_can_list_and_filter_roles(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/roles?search=manager&per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'roles' => ['items', 'meta', 'links'],
                ],
            ]);
    }

    public function test_admin_can_create_update_and_view_role(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/roles', [
            'name' => 'custom-ops',
            'display_name' => 'Custom Ops',
            'description' => 'Operations role',
            'permissions' => ['dashboard.view', 'users.view'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.role.name', 'custom-ops')
            ->assertJsonPath('data.role.display_name', 'Custom Ops');

        $uuid = $create->json('data.role.uuid');

        $this->getJson('/api/v1/roles/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.role.display_name', 'Custom Ops')
            ->assertJsonStructure(['data' => ['activity_history' => ['total', 'recent']]]);

        $this->putJson('/api/v1/roles/'.$uuid, [
            'display_name' => 'Custom Operations',
            'permissions' => ['dashboard.view', 'users.view', 'users.update'],
        ])
            ->assertOk()
            ->assertJsonPath('data.role.display_name', 'Custom Operations');
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->admin);
        $role = Role::findByName('manager', 'web');

        $this->deleteJson('/api/v1/roles/'.$role->uuid)
            ->assertStatus(403);
    }

    public function test_custom_role_can_be_soft_deleted_and_restored(): void
    {
        Sanctum::actingAs($this->admin);

        $role = Role::create([
            'name' => 'temp-role',
            'display_name' => 'Temp Role',
            'guard_name' => 'web',
            'is_system' => false,
        ]);

        $this->deleteJson('/api/v1/roles/'.$role->uuid)
            ->assertOk();

        $this->assertSoftDeleted('roles', ['id' => $role->id]);

        $this->postJson('/api/v1/roles/'.$role->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.role.name', 'temp-role');
    }

    public function test_permissions_groups_and_matrix_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/permissions')
            ->assertOk()
            ->assertJsonStructure(['data' => ['permissions' => ['items', 'meta']]]);

        $this->getJson('/api/v1/permissions/groups')
            ->assertOk()
            ->assertJsonStructure(['data' => ['groups']]);

        $role = Role::findByName('manager', 'web');

        $this->getJson('/api/v1/permissions/matrix?role='.$role->uuid)
            ->assertOk()
            ->assertJsonStructure(['data' => ['matrix']]);
    }

    public function test_can_sync_permissions_to_role(): void
    {
        Sanctum::actingAs($this->admin);

        $role = Role::create([
            'name' => 'sync-role',
            'display_name' => 'Sync Role',
            'guard_name' => 'web',
            'is_system' => false,
        ]);

        $this->postJson('/api/v1/roles/'.$role->uuid.'/permissions', [
            'permissions' => ['dashboard.view', 'reports.view'],
        ])
            ->assertOk();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('reports.view'));
    }

    public function test_can_assign_and_remove_user_roles(): void
    {
        Sanctum::actingAs($this->admin);
        $user = User::factory()->create();
        $role = Role::findByName('developer', 'web');

        $this->postJson('/api/v1/users/'.$user->uuid.'/roles', [
            'roles' => [$role->uuid],
        ])
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'developer');

        $this->assertTrue($user->fresh()->hasRole('developer'));

        $this->deleteJson('/api/v1/users/'.$user->uuid.'/roles/'.$role->uuid)
            ->assertOk();

        $this->assertFalse($user->fresh()->hasRole('developer'));
    }

    public function test_permission_middleware_blocks_unauthorized_role_create(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/roles', [
            'name' => 'blocked-role',
            'display_name' => 'Blocked',
        ])->assertForbidden();
    }

    public function test_create_role_validates_unique_name(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/roles', [
            'name' => 'manager',
            'display_name' => 'Duplicate Manager',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_permission_sync_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);
        $role = Role::findByName('qa-tester', 'web');

        $this->postJson('/api/v1/roles/'.$role->uuid.'/permissions', [
            'permissions' => ['does.not.exist'],
        ])->assertStatus(422);
    }
}
