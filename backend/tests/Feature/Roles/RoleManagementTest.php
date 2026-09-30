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
        $superAdmin = Role::findByName('super-admin', 'web');
        $companyAdmin = Role::findByName('company-admin', 'web');

        $this->assertTrue($superAdmin->hasPermissionTo(RolePermission::ASSIGN_USERS));
        $this->assertTrue($superAdmin->hasPermissionTo(RolePermission::ASSIGN_ROLES));
        $this->assertTrue($superAdmin->hasPermissionTo(RolePermission::MATRIX));
        $this->assertTrue($superAdmin->hasPermissionTo(RolePermission::VIEW_TRASH));
        $this->assertTrue($superAdmin->hasPermissionTo(RolePermission::FORCE_DELETE));
        $this->assertTrue($companyAdmin->hasPermissionTo(RolePermission::MATRIX));
        $this->assertTrue($companyAdmin->hasPermissionTo(RolePermission::VIEW_TRASH));
        $this->assertTrue($companyAdmin->hasPermissionTo(RolePermission::ASSIGN_ROLES));
        $this->assertTrue($companyAdmin->hasPermissionTo(RolePermission::FORCE_DELETE));

        $this->assertDatabaseHas('permissions', [
            'name' => RolePermission::MATRIX,
            'display_name' => 'Permission Matrix',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => RolePermission::VIEW_TRASH,
            'display_name' => 'Soft Delete View',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => RolePermission::ASSIGN_ROLES,
            'display_name' => 'Assign Roles',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => RolePermission::FORCE_DELETE,
            'display_name' => 'Permanent Delete Role',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => RolePermission::RESTORE,
            'display_name' => 'Restore Role',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => RolePermission::ASSIGN,
            'display_name' => 'Assign Permissions',
        ]);

        foreach ([
            'notifications.logs',
            'automation.rules',
            'workflows.designer',
            'scheduler.statistics',
            'ai.prompts',
            'sync.configs',
            'queue.failed',
            'support.tickets',
            'compliance.privacy',
            'analytics.dashboards',
            'audit.trail',
            'settings.security',
            'monitoring.alerts',
        ] as $permission) {
            $this->assertTrue(
                $companyAdmin->hasPermissionTo($permission),
                "Company admin is missing {$permission}."
            );
        }
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
            ->assertJsonPath('data.role.display_name', 'Custom Ops')
            ->assertJsonPath('data.role.created_by.uuid', $this->admin->uuid)
            ->assertJsonPath('data.role.created_by.full_name', $this->admin->full_name);

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

    public function test_role_screen_permissions_are_enforced_separately(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/permissions/matrix')->assertForbidden();
        $this->getJson('/api/v1/roles?trashed=only')->assertForbidden();

        $role = Role::findByName('developer', 'web');
        $user = User::factory()->create();

        $this->postJson('/api/v1/users/'.$user->uuid.'/roles', [
            'roles' => [$role->uuid],
        ])->assertForbidden();

        $this->postJson('/api/v1/roles/'.$role->uuid.'/permissions', [
            'permissions' => ['dashboard.view'],
        ])->assertForbidden();
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
