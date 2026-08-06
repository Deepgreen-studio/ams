<?php

namespace Database\Seeders;

use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Models\SupportSlaCalendar;
use App\Domains\Support\Models\SupportSlaEscalationRule;
use App\Domains\Support\Models\SupportSlaPolicy;
use Illuminate\Database\Seeder;

class SupportSlaSeeder extends Seeder
{
    public function run(): void
    {
        $calendar = SupportSlaCalendar::query()->firstOrCreate(
            [
                'company_id' => null,
                'name' => 'Global Business Hours',
            ],
            [
                'timezone' => 'UTC',
                'business_hours' => [
                    'monday' => ['09:00', '17:00'],
                    'tuesday' => ['09:00', '17:00'],
                    'wednesday' => ['09:00', '17:00'],
                    'thursday' => ['09:00', '17:00'],
                    'friday' => ['09:00', '17:00'],
                    'saturday' => null,
                    'sunday' => null,
                ],
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $policies = [
            [
                'code' => 'global-default',
                'name' => 'Global Default SLA',
                'priority' => null,
                'response' => 480,
                'resolution' => 2880,
                'is_default' => true,
                'rules' => [
                    [SupportSlaEscalationLevel::Level1, SupportSlaEscalationTrigger::ResponseAtRisk, 'support-agent'],
                    [SupportSlaEscalationLevel::Level2, SupportSlaEscalationTrigger::ResponseBreached, 'support-manager'],
                    [SupportSlaEscalationLevel::Manager, SupportSlaEscalationTrigger::ResolutionBreached, 'support-manager'],
                ],
            ],
            [
                'code' => 'global-high',
                'name' => 'High Priority SLA',
                'priority' => SupportTicketPriority::High->value,
                'response' => 120,
                'resolution' => 480,
                'is_default' => false,
                'rules' => [
                    [SupportSlaEscalationLevel::Level1, SupportSlaEscalationTrigger::ResponseAtRisk, 'support-agent'],
                    [SupportSlaEscalationLevel::Level2, SupportSlaEscalationTrigger::ResponseBreached, 'support-manager'],
                    [SupportSlaEscalationLevel::Level3, SupportSlaEscalationTrigger::ResolutionAtRisk, 'support-manager'],
                    [SupportSlaEscalationLevel::Administrator, SupportSlaEscalationTrigger::ResolutionBreached, 'super-admin'],
                ],
            ],
            [
                'code' => 'global-critical',
                'name' => 'Critical Priority SLA',
                'priority' => SupportTicketPriority::Critical->value,
                'response' => 30,
                'resolution' => 240,
                'is_default' => false,
                'rules' => [
                    [SupportSlaEscalationLevel::Level2, SupportSlaEscalationTrigger::ResponseAtRisk, 'support-manager'],
                    [SupportSlaEscalationLevel::Manager, SupportSlaEscalationTrigger::ResponseBreached, 'support-manager'],
                    [SupportSlaEscalationLevel::Administrator, SupportSlaEscalationTrigger::ResolutionBreached, 'super-admin'],
                ],
            ],
            [
                'code' => 'global-emergency',
                'name' => 'Emergency Priority SLA',
                'priority' => SupportTicketPriority::Emergency->value,
                'response' => 15,
                'resolution' => 120,
                'is_default' => false,
                'rules' => [
                    [SupportSlaEscalationLevel::Manager, SupportSlaEscalationTrigger::ResponseAtRisk, 'support-manager'],
                    [SupportSlaEscalationLevel::Administrator, SupportSlaEscalationTrigger::ResponseBreached, 'super-admin'],
                    [SupportSlaEscalationLevel::Administrator, SupportSlaEscalationTrigger::ResolutionBreached, 'super-admin'],
                ],
            ],
        ];

        foreach ($policies as $definition) {
            $policy = SupportSlaPolicy::query()->updateOrCreate(
                [
                    'company_id' => null,
                    'code' => $definition['code'],
                ],
                [
                    'support_sla_calendar_id' => $calendar->id,
                    'name' => $definition['name'],
                    'priority' => $definition['priority'],
                    'category' => null,
                    'response_target_minutes' => $definition['response'],
                    'resolution_target_minutes' => $definition['resolution'],
                    'at_risk_percent' => 80,
                    'business_hours_only' => false,
                    'is_default' => $definition['is_default'],
                    'is_active' => true,
                    'description' => 'Seeded global SLA policy',
                ]
            );

            if ($policy->escalationRules()->exists()) {
                continue;
            }

            foreach ($definition['rules'] as $index => [$level, $trigger, $role]) {
                SupportSlaEscalationRule::query()->create([
                    'support_sla_policy_id' => $policy->id,
                    'level' => $level->value,
                    'trigger' => $trigger->value,
                    'sort_order' => $index,
                    'notify_role' => $role,
                    'reassign_to_manager' => $level === SupportSlaEscalationLevel::Manager,
                    'is_active' => true,
                ]);
            }
        }
    }
}
