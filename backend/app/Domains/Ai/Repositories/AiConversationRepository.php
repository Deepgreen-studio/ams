<?php

namespace App\Domains\Ai\Repositories;

use App\Domains\Ai\Models\AiConversation;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AiConversationRepository extends BaseRepository
{
    public function __construct(AiConversation $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?AiConversation
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AiConversation|null $conversation */
        $conversation = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $conversation;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): AiConversation
    {
        $conversation = $this->findByIdentifier($identifier, $withTrashed);
        if (! $conversation) {
            abort(404, 'AI conversation not found.');
        }

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with([
                'user:id,uuid,full_name,email',
                'provider:id,uuid,name,driver,slug',
                'prompt:id,uuid,key,name,feature',
            ])
            ->withCount('messages')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'active' => $this->model->newQuery()->where('status', 'active')->count(),
            'archived' => $this->model->newQuery()->where('status', 'archived')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['user_id'] ?? null)) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (! blank($filters['feature'] ?? null)) {
            $query->where('feature', $filters['feature']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('context_type', 'like', "%{$search}%")
                    ->orWhere('context_id', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
