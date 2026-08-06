<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Enums\SupportSlaEscalationStatus;
use App\Domains\Support\Models\SupportSlaEscalation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupportSlaEscalationRepository
{
    public function findByIdentifierOrFail(string $identifier): SupportSlaEscalation
    {
        return SupportSlaEscalation::query()
            ->with([
                'ticket.assignee:id,uuid,full_name,email',
                'ticket.company:id,uuid,company_name',
                'policy:id,uuid,name,code',
                'rule',
                'assignee:id,uuid,full_name,email',
                'acknowledger:id,uuid,full_name,email',
            ])
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateQueue(array $filters = []): LengthAwarePaginator
    {
        $query = SupportSlaEscalation::query()->with([
            'ticket:id,uuid,ticket_number,subject,priority,status,sla_status,escalation_level,company_id,assigned_to,first_response_due_at,resolution_due_at',
            'ticket.company:id,uuid,company_name',
            'ticket.assignee:id,uuid,full_name,email',
            'policy:id,uuid,name',
            'assignee:id,uuid,full_name,email',
        ]);

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', [
                SupportSlaEscalationStatus::Pending->value,
                SupportSlaEscalationStatus::Notified->value,
                SupportSlaEscalationStatus::Acknowledged->value,
            ]);
        }

        if (! blank($filters['level'] ?? null)) {
            $query->where('level', $filters['level']);
        }

        if (! blank($filters['metric'] ?? null)) {
            $query->where('metric', $filters['metric']);
        }

        return $query->orderByDesc('triggered_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function existsForTicketRule(int $ticketId, int $ruleId): bool
    {
        return SupportSlaEscalation::query()
            ->where('support_ticket_id', $ticketId)
            ->where('support_sla_escalation_rule_id', $ruleId)
            ->whereNotIn('status', [SupportSlaEscalationStatus::Cancelled->value])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportSlaEscalation
    {
        return SupportSlaEscalation::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportSlaEscalation $escalation, array $data): SupportSlaEscalation
    {
        $escalation->fill($data);
        $escalation->save();

        return $escalation->refresh();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(?int $companyId = null): array
    {
        $query = SupportSlaEscalation::query();
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $open = (clone $query)->whereIn('status', [
            SupportSlaEscalationStatus::Pending->value,
            SupportSlaEscalationStatus::Notified->value,
            SupportSlaEscalationStatus::Acknowledged->value,
        ])->count();

        return [
            'open' => $open,
            'pending' => (clone $query)->where('status', SupportSlaEscalationStatus::Pending->value)->count(),
            'acknowledged' => (clone $query)->where('status', SupportSlaEscalationStatus::Acknowledged->value)->count(),
            'resolved' => (clone $query)->where('status', SupportSlaEscalationStatus::Resolved->value)->count(),
        ];
    }
}
