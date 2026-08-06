<?php

namespace App\Domains\Companies\Controllers;

use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Requests\StoreTeamRequest;
use App\Domains\Companies\Requests\UpdateTeamRequest;
use App\Domains\Companies\Repositories\TeamRepository;
use App\Domains\Companies\Resources\TeamResource;
use App\Domains\Companies\Services\TeamService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TeamService $teamService,
        private readonly TeamRepository $teamRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTeams', Company::class);

        $teams = $this->teamService->list($request->only([
            'company', 'department', 'search', 'status', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'teams' => [
                'items' => TeamResource::collection($teams->items()),
                'meta' => [
                    'current_page' => $teams->currentPage(),
                    'last_page' => $teams->lastPage(),
                    'per_page' => $teams->perPage(),
                    'total' => $teams->total(),
                ],
            ],
        ]);
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $this->authorize('manageTeams', Company::class);

        /** @var User $actor */
        $actor = $request->user();
        $team = $this->teamService->create($request->validated(), $actor);

        return ApiResponse::success([
            'team' => new TeamResource($team),
        ], 'Team created successfully.', 201);
    }

    public function update(UpdateTeamRequest $request, string $team): JsonResponse
    {
        $existing = $this->teamRepository->findByIdentifierOrFail($team);
        $this->authorize('updateTeam', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->teamService->update($team, $request->validated(), $actor);

        return ApiResponse::success([
            'team' => new TeamResource($updated),
        ], 'Team updated successfully.');
    }

    public function destroy(Request $request, string $team): JsonResponse
    {
        $existing = $this->teamRepository->findByIdentifierOrFail($team);
        $this->authorize('deleteTeam', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->teamService->delete($team, $actor);

        return ApiResponse::success(null, 'Team deleted successfully.');
    }
}
