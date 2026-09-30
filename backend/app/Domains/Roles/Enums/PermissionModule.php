<?php

namespace App\Domains\Roles\Enums;

/**
 * Module catalog used to generate grouped CRUD permissions.
 */
final class PermissionModule
{
    /**
     * @return array<string, array{label: string, actions: list<string>, labels?: array<string, string>}>
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
                'labels' => [
                    'assign-roles' => 'Assign Roles to Users',
                ],
            ],
            'roles' => [
                'label' => 'Roles',
                'actions' => array_merge($crud, [
                    'restore',
                    'force-delete',
                    'assign',
                    'assign-roles',
                    'matrix',
                    'view-trash',
                ]),
                'labels' => [
                    'view' => 'View Role',
                    'create' => 'Create Role',
                    'update' => 'Edit Role',
                    'delete' => 'Soft Delete Role',
                    'restore' => 'Restore Role',
                    'force-delete' => 'Permanent Delete Role',
                    'assign' => 'Assign Permissions',
                    'assign-roles' => 'Assign Roles',
                    'matrix' => 'Permission Matrix',
                    'view-trash' => 'Soft Delete View',
                ],
            ],
            'companies' => [
                'label' => 'Companies',
                'actions' => array_merge($crud, [
                    'restore',
                    'force-delete',
                    'view-trash',
                    'console',
                    'profile',
                    'manage',
                ]),
                'labels' => [
                    'view' => 'View Company',
                    'create' => 'Create Company',
                    'update' => 'Edit Company',
                    'delete' => 'Soft Delete Company',
                    'restore' => 'Restore Company',
                    'force-delete' => 'Permanent Delete Company',
                    'view-trash' => 'Soft Deleted View',
                    'console' => 'Company Console',
                    'profile' => 'Company Profile',
                    'manage' => 'Manage Company',
                ],
            ],
            'departments' => [
                'label' => 'Departments',
                'actions' => $crud,
            ],
            'teams' => [
                'label' => 'Teams',
                'actions' => $crud,
            ],
            'locations' => [
                'label' => 'Locations',
                'actions' => $crud,
            ],
            'applications' => [
                'label' => 'Applications',
                'actions' => $crud,
            ],
            'customers' => [
                'label' => 'Customers',
                'actions' => array_merge($crud, ['restore', 'export', 'anonymize']),
            ],
            'customer-contacts' => [
                'label' => 'Customer Contacts',
                'actions' => array_merge($crud, ['restore', 'export', 'import']),
            ],
            'customer-applications' => [
                'label' => 'Customer Applications',
                'actions' => array_merge($crud, ['restore']),
            ],
            'customer-subscriptions' => [
                'label' => 'Customer Subscriptions',
                'actions' => array_merge($crud, ['restore', 'cancel']),
            ],
            'customer-licenses' => [
                'label' => 'Customer Licenses',
                'actions' => array_merge($crud, ['restore', 'revoke']),
            ],
            'customer-documents' => [
                'label' => 'Customer Documents',
                'actions' => array_merge($crud, ['restore', 'download']),
            ],
            'customer-communications' => [
                'label' => 'Customer Communications',
                'actions' => array_merge($crud, ['restore']),
            ],
            'customer-analytics' => [
                'label' => 'Customer Analytics',
                'actions' => ['view', 'refresh'],
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
