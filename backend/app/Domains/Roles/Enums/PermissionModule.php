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
                'actions' => array_merge($crud, ['restore', 'force-delete', 'view-trash']),
                'labels' => [
                    'view' => 'View Application',
                    'create' => 'Create Application',
                    'update' => 'Edit Application',
                    'delete' => 'Soft Delete Application',
                    'restore' => 'Restore Application',
                    'force-delete' => 'Permanent Delete Application',
                    'view-trash' => 'Soft Deleted View',
                ],
            ],
            'customers' => [
                'label' => 'Customers',
                'actions' => array_merge($crud, ['restore', 'force-delete', 'view-trash', 'export', 'anonymize']),
                'labels' => [
                    'view' => 'View Customer',
                    'create' => 'Create Customer',
                    'update' => 'Edit Customer',
                    'delete' => 'Soft Delete Customer',
                    'restore' => 'Restore Customer',
                    'force-delete' => 'Permanent Delete Customer',
                    'view-trash' => 'Soft Deleted View',
                ],
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
            'webhooks' => [
                'label' => 'Webhooks',
                'actions' => ['view', 'create', 'update', 'delete', 'restore', 'force-delete', 'view-trash', 'logs', 'events', 'docs', 'test'],
                'labels' => [
                    'view' => 'View Webhook',
                    'create' => 'Create Webhook',
                    'update' => 'Edit Webhook',
                    'delete' => 'Soft Delete Webhook',
                    'restore' => 'Restore Webhook',
                    'force-delete' => 'Permanent Delete Webhook',
                    'view-trash' => 'Soft Deleted View',
                    'logs' => 'Webhook Logs',
                    'events' => 'Webhook Events',
                    'docs' => 'API Docs',
                    'test' => 'Test Webhook',
                ],
            ],
            'integrations' => [
                'label' => 'Integrations',
                'actions' => array_merge($crud, ['restore', 'force-delete', 'view-trash', 'manage']),
                'labels' => [
                    'view' => 'View Integration',
                    'create' => 'Create Integration',
                    'update' => 'Edit Integration',
                    'delete' => 'Soft Delete Integration',
                    'restore' => 'Restore Integration',
                    'force-delete' => 'Permanent Delete Integration',
                    'view-trash' => 'Soft Deleted View',
                    'manage' => 'Manage Integration',
                ],
            ],
            'sync' => [
                'label' => 'Sync',
                'actions' => ['view', 'configs', 'history', 'logs'],
                'labels' => [
                    'view' => 'Sync Dashboard',
                    'configs' => 'Sync Configs',
                    'history' => 'Sync History',
                    'logs' => 'Sync Logs',
                ],
            ],
            'queue' => [
                'label' => 'Queue Processing',
                'actions' => ['view', 'manage', 'retry', 'running', 'failed', 'statistics'],
                'labels' => [
                    'view' => 'Queue Dashboard',
                    'running' => 'Running Jobs',
                    'failed' => 'Failed Jobs',
                    'statistics' => 'Queue Statistics',
                ],
            ],
            'monitoring' => [
                'label' => 'Monitoring & Health',
                'actions' => ['view', 'manage', 'realtime', 'api', 'webhooks', 'queue', 'integrations', 'timeline', 'history', 'alerts'],
                'labels' => [
                    'view' => 'Health Dashboard',
                    'realtime' => 'Real-Time Monitor',
                    'api' => 'API Monitor',
                    'webhooks' => 'Webhook Monitor',
                    'queue' => 'Queue Monitor',
                    'integrations' => 'Integration Monitor',
                    'timeline' => 'Incident Timeline',
                    'history' => 'Response History',
                    'alerts' => 'Alert Configuration',
                ],
            ],
            'releases' => [
                'label' => 'Releases',
                'actions' => $crud,
            ],
            'content' => [
                'label' => 'Content',
                'actions' => array_merge($crud, ['publish', 'submit', 'review', 'approve']),
                'labels' => [
                    'review' => 'Content Approval Queue',
                    'approve' => 'Approve Content',
                ],
            ],
            'support' => [
                'label' => 'Support',
                'actions' => array_merge($crud, ['manage', 'tickets', 'board', 'queue', 'assignment', 'sla', 'knowledge', 'canned']),
                'labels' => [
                    'view' => 'Support Dashboard',
                    'tickets' => 'Tickets',
                    'board' => 'Kanban Board',
                    'queue' => 'Ticket Queue',
                    'assignment' => 'Assignment',
                    'sla' => 'SLA',
                    'knowledge' => 'Knowledge Base',
                    'canned' => 'Canned Responses',
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'actions' => array_merge($crud, ['approve', 'publish', 'center', 'unread', 'history', 'preferences', 'templates', 'logs']),
                'labels' => [
                    'view' => 'Notification Dashboard',
                    'approve' => 'Template Approvals',
                    'center' => 'Notification Center',
                    'unread' => 'Unread Notifications',
                    'history' => 'Notification History',
                    'preferences' => 'Notification Preferences',
                    'templates' => 'Notification Templates',
                    'logs' => 'Notification Logs',
                ],
            ],
            'automation' => [
                'label' => 'Automation',
                'actions' => array_merge($crud, ['manage', 'rules', 'history']),
                'labels' => [
                    'view' => 'Automation Dashboard',
                    'rules' => 'Automation Rules',
                    'history' => 'Automation History',
                ],
            ],
            'workflows' => [
                'label' => 'Workflows',
                'actions' => array_merge($crud, ['manage', 'approve', 'designer', 'monitor', 'history']),
                'labels' => [
                    'view' => 'Workflow Dashboard',
                    'approve' => 'Approval Queue',
                    'designer' => 'Workflow Designer',
                    'monitor' => 'Workflow Monitor',
                    'history' => 'Workflow History',
                ],
            ],
            'scheduler' => [
                'label' => 'Scheduler',
                'actions' => array_merge($crud, ['manage', 'retry', 'jobs', 'history', 'running', 'failed', 'logs', 'statistics']),
                'labels' => [
                    'view' => 'Scheduler Dashboard',
                    'jobs' => 'Scheduled Jobs',
                    'history' => 'Scheduler History',
                    'running' => 'Running Jobs',
                    'failed' => 'Failed Jobs',
                    'logs' => 'Scheduler Logs',
                    'statistics' => 'Scheduler Statistics',
                ],
            ],
            'ai' => [
                'label' => 'AI Assistant',
                'actions' => array_merge($crud, ['manage', 'chat', 'settings', 'prompts', 'conversations', 'analytics', 'logs']),
                'labels' => [
                    'view' => 'AI Dashboard',
                    'settings' => 'AI Settings',
                    'prompts' => 'Prompt Manager',
                    'conversations' => 'AI Conversations',
                    'analytics' => 'AI Usage Analytics',
                    'logs' => 'AI Logs',
                ],
            ],
            'analytics' => [
                'label' => 'Analytics',
                'actions' => array_merge($crud, [
                    'export',
                    'manage',
                    'dashboards',
                    'templates',
                    'reports',
                    'saved-reports',
                    'saved-views',
                    'events',
                    'business',
                    'executive',
                    'security',
                    'operational',
                ]),
                'labels' => [
                    'view' => 'Analytics Overview',
                    'dashboards' => 'Analytics Dashboards',
                    'templates' => 'Analytics Templates',
                    'reports' => 'Analytics Reports',
                    'saved-reports' => 'Saved Reports',
                    'saved-views' => 'Saved Views',
                    'events' => 'Analytics Events',
                    'business' => 'Business Analytics',
                    'executive' => 'Executive Analytics',
                    'security' => 'Security Analytics',
                    'operational' => 'Operational Analytics',
                ],
            ],
            'compliance' => [
                'label' => 'Compliance',
                'actions' => array_merge($crud, ['manage', 'cases', 'privacy', 'consents', 'breaches', 'dpia', 'policies', 'reports']),
                'labels' => [
                    'view' => 'Compliance Dashboard',
                    'cases' => 'Compliance Cases',
                    'privacy' => 'Privacy Requests',
                    'consents' => 'Consent',
                    'breaches' => 'Data Breaches',
                    'dpia' => 'DPIA',
                    'policies' => 'Policies',
                    'reports' => 'Compliance Reports',
                ],
            ],
            'reports' => [
                'label' => 'Reports',
                'actions' => ['view', 'export'],
            ],
            'settings' => [
                'label' => 'Settings',
                'actions' => ['view', 'update', 'manage', 'email', 'storage', 'security', 'api', 'queue', 'media', 'files'],
                'labels' => [
                    'view' => 'General Settings',
                    'email' => 'Email Settings',
                    'storage' => 'Storage Settings',
                    'security' => 'Security Settings',
                    'api' => 'API Settings',
                    'queue' => 'Queue Settings',
                    'media' => 'Media Library',
                    'files' => 'File Manager',
                ],
            ],
            'audit' => [
                'label' => 'Audit & Monitoring',
                'actions' => ['view', 'export', 'manage', 'trail', 'login', 'events', 'api', 'errors'],
                'labels' => [
                    'view' => 'Activity Logs',
                    'trail' => 'Audit Trail',
                    'login' => 'Login History',
                    'events' => 'System Events',
                    'api' => 'API Logs',
                    'errors' => 'Error Logs',
                ],
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
