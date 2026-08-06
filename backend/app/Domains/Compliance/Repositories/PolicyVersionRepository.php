<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PolicyVersion;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PolicyVersionRepository extends BaseRepository
{
    public function __construct(PolicyVersion $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Collection<int, PolicyVersion>
     */
    public function forPolicy(int $policyId): Collection
    {
        return $this->model->newQuery()
            ->with(['creator:id,uuid,full_name,email'])
            ->where('policy_id', $policyId)
            ->orderByDesc('version')
            ->get();
    }

    public function findForPolicy(int $policyId, string $identifier): PolicyVersion
    {
        $query = $this->model->newQuery()->where('policy_id', $policyId);

        /** @var PolicyVersion|null $version */
        $version = $query->where(function ($builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('version', (int) $identifier)
                    ->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $version) {
            abort(404, 'Policy version not found.');
        }

        return $version;
    }

    public function nextVersionNumber(int $policyId): int
    {
        $max = (int) $this->model->newQuery()
            ->where('policy_id', $policyId)
            ->max('version');

        return $max + 1;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVersion(array $data): PolicyVersion
    {
        /** @var PolicyVersion $version */
        $version = $this->model->newQuery()->create($data);

        return $version->fresh(['creator']) ?? $version;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function recordForPolicy(
        PolicyDocument $policy,
        int $versionNumber,
        string $status,
        string $reason,
        ?int $createdBy,
        array $snapshot,
        bool $isRestore = false,
        ?int $restoredFrom = null
    ): PolicyVersion {
        return $this->createVersion([
            'policy_id' => $policy->id,
            'version' => $versionNumber,
            'status' => $status,
            'title' => (string) ($snapshot['title'] ?? $policy->title),
            'body' => $snapshot['body'] ?? $policy->body,
            'snapshot' => $snapshot,
            'reason' => $reason,
            'is_restore' => $isRestore,
            'restored_from_version' => $restoredFrom,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
    }
}
