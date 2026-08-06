<?php

namespace Database\Seeders;

use App\Domains\Roles\Enums\DefaultRole;
use App\Domains\Roles\Enums\PermissionModule;
use App\Domains\Roles\Models\Permission;
use App\Domains\Roles\Models\PermissionGroup;
use App\Domains\Roles\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';
        $sort = 0;
        $groupsByModule = [];

        foreach (PermissionModule::catalog() as $module => $meta) {
            $sort += 10;
            $group = PermissionGroup::query()->firstOrCreate(
                ['slug' => $module],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $meta['label'],
                    'module' => $module,
                    'description' => $meta['label'].' module permissions.',
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );

            $group->fill([
                'name' => $meta['label'],
                'module' => $module,
                'description' => $meta['label'].' module permissions.',
                'sort_order' => $sort,
                'is_active' => true,
            ]);

            if (blank($group->uuid)) {
                $group->uuid = (string) Str::uuid();
            }

            $group->save();

            $groupsByModule[$module] = $group;

            foreach ($meta['actions'] as $action) {
                $name = "{$module}.{$action}";
                Permission::findOrCreate($name, $guard);

                Permission::query()
                    ->where('name', $name)
                    ->where('guard_name', $guard)
                    ->update([
                        'display_name' => Str::of($action)->replace('-', ' ')->title().' '.$meta['label'],
                        'module' => $module,
                        'description' => "Allows {$action} access in {$meta['label']}.",
                        'permission_group_id' => $group->id,
                    ]);
            }
        }

        foreach (DefaultRole::definitions() as $name => $definition) {
            $role = Role::findOrCreate($name, $guard);
            $role->fill([
                'display_name' => $definition['display_name'],
                'description' => $definition['description'],
                'is_system' => $definition['is_system'],
            ]);

            if (blank($role->uuid)) {
                $role->uuid = (string) Str::uuid();
            }

            $role->save();
        }

        $allPermissions = Permission::query()->where('guard_name', $guard)->get();

        Role::findByName('super-admin', $guard)->syncPermissions($allPermissions);

        $this->syncNamedPermissions('company-admin', [
            'dashboard.view',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.restore', 'users.assign-roles',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.restore', 'roles.assign',
            'companies.view', 'companies.create', 'companies.update', 'companies.delete', 'companies.restore', 'companies.manage',
            'applications.view', 'applications.create', 'applications.update', 'applications.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customers.restore',
            'integrations.view', 'integrations.create', 'integrations.update',
            'queue.view', 'queue.retry',
            'monitoring.view',
            'releases.view', 'releases.create', 'releases.update',
            'content.view', 'content.create', 'content.update', 'content.publish',
            'content.submit', 'content.review', 'content.approve',
            'support.view', 'support.create', 'support.update', 'support.manage',
            'notifications.view', 'notifications.create', 'notifications.update',
            'notifications.approve', 'notifications.publish',
            'automation.view', 'automation.create', 'automation.update', 'automation.delete', 'automation.manage',
            'workflows.view', 'workflows.create', 'workflows.update', 'workflows.delete', 'workflows.manage', 'workflows.approve',
            'scheduler.view', 'scheduler.create', 'scheduler.update', 'scheduler.delete', 'scheduler.manage', 'scheduler.retry',
            'ai.view', 'ai.create', 'ai.update', 'ai.delete', 'ai.manage', 'ai.chat',
            'analytics.view', 'analytics.create', 'analytics.update', 'analytics.delete',
            'analytics.export', 'analytics.manage',
            'reports.view', 'reports.export',
            'settings.view', 'settings.update', 'settings.manage',
            'audit.view', 'audit.export', 'audit.manage',
        ], $guard);

        $this->syncNamedPermissions('manager', [
            'dashboard.view',
            'users.view', 'users.update',
            'roles.view',
            'companies.view',
            'applications.view', 'customers.view',
            'releases.view', 'content.view',
            'support.view', 'support.update',
            'analytics.view', 'analytics.create', 'analytics.update', 'analytics.export',
            'reports.view',
            'settings.view',
            'audit.view',
        ], $guard);

        $this->syncNamedPermissions('developer', [
            'dashboard.view',
            'applications.view', 'applications.create', 'applications.update',
            'integrations.view', 'integrations.create', 'integrations.update', 'integrations.manage',
            'queue.view', 'queue.manage', 'queue.retry',
            'monitoring.view', 'monitoring.manage',
            'scheduler.view', 'scheduler.create', 'scheduler.update', 'scheduler.manage', 'scheduler.retry',
            'ai.view', 'ai.create', 'ai.update', 'ai.manage', 'ai.chat',
            'releases.view', 'releases.create', 'releases.update', 'releases.delete',
            'customers.view',
            'settings.view',
        ], $guard);

        $this->syncNamedPermissions('qa-tester', [
            'dashboard.view',
            'applications.view',
            'releases.view',
            'customers.view',
            'support.view', 'support.create',
            'content.view',
        ], $guard);

        $this->syncNamedPermissions('support-manager', [
            'dashboard.view',
            'customers.view', 'customers.update',
            'support.view', 'support.create', 'support.update', 'support.delete', 'support.manage',
            'notifications.view', 'notifications.create',
            'automation.view', 'automation.create', 'automation.update',
            'workflows.view', 'workflows.approve',
            'ai.view', 'ai.chat',
            'reports.view',
        ], $guard);

        $this->syncNamedPermissions('support-agent', [
            'dashboard.view',
            'customers.view',
            'support.view', 'support.create', 'support.update',
            'notifications.view',
            'workflows.view', 'workflows.approve',
            'ai.view', 'ai.chat',
        ], $guard);

        $this->syncNamedPermissions('content-manager', [
            'dashboard.view',
            'content.view', 'content.create', 'content.update', 'content.delete',
            'content.submit', 'content.review', 'content.approve',
            'notifications.view', 'notifications.create',
        ], $guard);

        $this->syncNamedPermissions('content-writer', [
            'dashboard.view',
            'content.view', 'content.create', 'content.update', 'content.submit',
            'notifications.view',
        ], $guard);

        $this->syncNamedPermissions('content-editor', [
            'dashboard.view',
            'content.view', 'content.create', 'content.update', 'content.submit', 'content.review',
            'notifications.view', 'notifications.create',
        ], $guard);

        $this->syncNamedPermissions('compliance-officer', [
            'dashboard.view',
            'compliance.view', 'compliance.create', 'compliance.update', 'compliance.delete', 'compliance.manage',
            'users.view',
            'reports.view', 'reports.export',
            'analytics.view',
            'audit.view', 'audit.export',
        ], $guard);

        $this->syncNamedPermissions('customer', [
            'dashboard.view',
            'applications.view',
            'support.view', 'support.create',
            'content.view',
        ], $guard);

        $this->syncNamedPermissions('read-only-user', [
            'dashboard.view',
            'users.view',
            'roles.view',
            'companies.view',
            'applications.view',
            'customers.view',
            'releases.view',
            'content.view',
            'support.view',
            'analytics.view',
            'reports.view',
            'settings.view',
            'audit.view',
        ], $guard);

        // Backward-compatible alias used by earlier milestones/tests.
        if (! Role::query()->where('name', 'admin')->exists()) {
            $alias = Role::findOrCreate('admin', $guard);
            $alias->fill([
                'display_name' => 'Admin (Legacy)',
                'description' => 'Legacy alias mirrored from Company Admin.',
                'is_system' => true,
            ]);
            if (blank($alias->uuid)) {
                $alias->uuid = (string) Str::uuid();
            }
            $alias->save();
            $alias->syncPermissions(Role::findByName('company-admin', $guard)->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function syncNamedPermissions(string $roleName, array $permissions, string $guard): void
    {
        Role::findByName($roleName, $guard)->syncPermissions($permissions);
    }
}
