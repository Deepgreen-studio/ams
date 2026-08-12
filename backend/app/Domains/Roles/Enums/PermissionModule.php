<?php

namespace App\Domains\Roles\Enums;

/**
 * Module catalog used to generate grouped CRUD permissions.
 */
final class PermissionModule
{
    /**
     * @return array<string, array{label: string, actions: list<string>}>
     */
    public static function catalog(): array
    {
        $crud = ['view', 'create', 'update', 'delete'];

        return [
            'authentication' => [
                'label' => 'Authentication',
                'actions' => ['view', 'manage'],
            ],
            'dashboard' => [
                'label' => 'Dashboard',
                'actions' => ['view'],
            ],
            'users' => [
                'label' => 'Users',
                'actions' => array_merge($crud, ['restore', 'force-delete', 'assign-roles']),
            ],
            'roles' => [
                'label' => 'Roles',
                'actions' => array_merge($crud, ['restore', 'force-delete', 'assign']),
            ],
            'companies' => [
                'label' => 'Companies',
                'actions' => array_merge($crud, ['restore', 'manage']),
            ],
            'applications' => [
                'label' => 'Applications',
                'actions' => $crud,
            ],
            'customers' => [
                'label' => 'Customers',
                'actions' => array_merge($crud, ['restore']),
            ],
            'integrations' => [
                'label' => 'Integrations',
                'actions' => array_merge($crud, ['manage']),
            ],
            'queue' => [
                'label' => 'Queue Processing',
                'actions' => ['view', 'manage', 'retry'],
            ],
            'monitoring' => [
                'label' => 'Monitoring & Health',
                'actions' => ['view', 'manage'],
            ],
            'releases' => [
                'label' => 'Releases',
                'actions' => $crud,
            ],
            'content' => [
                'label' => 'Content',
                'actions' => array_merge($crud, ['publish', 'submit', 'review', 'approve']),
            ],
            'support' => [
                'label' => 'Support',
                'actions' => array_merge($crud, ['manage']),
            ],
            'notifications' => [
                'label' => 'Notifications',
                'actions' => array_merge($crud, ['approve', 'publish']),
            ],
            'automation' => [
                'label' => 'Automation',
                'actions' => array_merge($crud, ['manage']),
            ],
            'workflows' => [
                'label' => 'Workflows',
                'actions' => array_merge($crud, ['manage', 'approve']),
            ],
            'scheduler' => [
                'label' => 'Scheduler',
                'actions' => array_merge($crud, ['manage', 'retry']),
            ],
            'ai' => [
                'label' => 'AI Assistant',
                'actions' => array_merge($crud, ['manage', 'chat']),
            ],
            'analytics' => [
                'label' => 'Analytics',
                'actions' => array_merge($crud, ['export', 'manage']),
            ],
            'compliance' => [
                'label' => 'Compliance',
                'actions' => array_merge($crud, ['manage']),
            ],
            'reports' => [
                'label' => 'Reports',
                'actions' => ['view', 'export'],
            ],
            'settings' => [
                'label' => 'Settings',
                'actions' => ['view', 'update', 'manage'],
            ],
            'audit' => [
                'label' => 'Audit & Monitoring',
                'actions' => ['view', 'export', 'manage'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::catalog() as $module => $meta) {
            foreach ($meta['actions'] as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }
}
