<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Events\AuditCreated;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Repositories\AuditRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditTrailService
{
    public function __construct(
        private readonly AuditRepository $auditRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->auditRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): AuditLog
    {
        return $this->auditRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $module,
        string $action,
        ?User $actor = null,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?Request $request = null,
        ?int $companyId = null
    ): AuditLog {
        $changed = $this->diff($before ?? [], $after ?? []);

        /** @var AuditLog $log */
        $log = $this->auditRepository->create([
            'user_id' => $actor?->id,
            'company_id' => $companyId,
            'module' => $module,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'before_data' => $before,
            'after_data' => $after,
            'changed_fields' => $changed,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        event(new AuditCreated($log));

        return $log;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    protected function diff(array $before, array $after): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changed = [];

        foreach ($keys as $key) {
            $left = $before[$key] ?? null;
            $right = $after[$key] ?? null;
            if ($left != $right) {
                $changed[] = (string) $key;
            }
        }

        return $changed;
    }
}
