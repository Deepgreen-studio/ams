<?php

namespace App\Domains\Automation\Listeners;

use App\Domains\Automation\Events\AutomationRuleCreated;
use App\Domains\Automation\Events\AutomationRuleDeleted;
use App\Domains\Automation\Events\AutomationRuleUpdated;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LogAutomationActivity
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    public function handleAutomationRuleCreated(AutomationRuleCreated $event): void
    {
        $this->log($event->actor, $event->rule, 'automation_rule_created', 'Automation rule created');
    }

    public function handleAutomationRuleUpdated(AutomationRuleUpdated $event): void
    {
        $this->log($event->actor, $event->rule, 'automation_rule_updated', 'Automation rule updated');
    }

    public function handleAutomationRuleDeleted(AutomationRuleDeleted $event): void
    {
        $this->log($event->actor, $event->rule, 'automation_rule_deleted', 'Automation rule deleted');
    }

    private function log(User $actor, Model $subject, string $event, string $description): void
    {
        activity('automation')
            ->causedBy($actor)
            ->performedOn($subject)
            ->withProperties([
                'ip' => $this->request?->ip(),
                'user_agent' => $this->request?->userAgent(),
            ])
            ->event($event)
            ->log($description);
    }
}
