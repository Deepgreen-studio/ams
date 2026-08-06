<?php

namespace App\Domains\Roles\Enums;

/**
 * Default enterprise roles (machine name => display metadata).
 */
final class DefaultRole
{
    /**
     * @return array<string, array{display_name: string, description: string, is_system: bool}>
     */
    public static function definitions(): array
    {
        return [
            'super-admin' => [
                'display_name' => 'Super Admin',
                'description' => 'Full platform access across all modules.',
                'is_system' => true,
            ],
            'company-admin' => [
                'display_name' => 'Company Admin',
                'description' => 'Administers company-scoped resources and users.',
                'is_system' => true,
            ],
            'manager' => [
                'display_name' => 'Manager',
                'description' => 'Operational oversight with elevated view/update rights.',
                'is_system' => true,
            ],
            'developer' => [
                'display_name' => 'Developer',
                'description' => 'Builds and maintains applications, integrations, and releases.',
                'is_system' => true,
            ],
            'qa-tester' => [
                'display_name' => 'QA Tester',
                'description' => 'Validates releases, applications, and support workflows.',
                'is_system' => true,
            ],
            'support-manager' => [
                'display_name' => 'Support Manager',
                'description' => 'Manages support teams and escalations.',
                'is_system' => true,
            ],
            'support-agent' => [
                'display_name' => 'Support Agent',
                'description' => 'Handles day-to-day customer support tickets.',
                'is_system' => true,
            ],
            'content-manager' => [
                'display_name' => 'Content Manager',
                'description' => 'Manages content through review and approval stages.',
                'is_system' => true,
            ],
            'content-writer' => [
                'display_name' => 'Content Writer',
                'description' => 'Creates content drafts and submits them for review.',
                'is_system' => true,
            ],
            'content-editor' => [
                'display_name' => 'Content Editor',
                'description' => 'Reviews submitted content before managerial approval.',
                'is_system' => true,
            ],
            'compliance-officer' => [
                'display_name' => 'Compliance Officer',
                'description' => 'Owns compliance, privacy, and audit workflows.',
                'is_system' => true,
            ],
            'customer' => [
                'display_name' => 'Customer',
                'description' => 'External customer portal access.',
                'is_system' => true,
            ],
            'read-only-user' => [
                'display_name' => 'Read Only User',
                'description' => 'View-only access across permitted modules.',
                'is_system' => true,
            ],
        ];
    }
}
