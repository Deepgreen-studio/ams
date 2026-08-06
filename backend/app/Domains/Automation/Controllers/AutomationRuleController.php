<?php

namespace App\Domains\Automation\Controllers;

use App\Domains\Automation\Models\AutomationRule;
use App\Domains\Automation\Requests\IndexAutomationRuleRequest;
use App\Domains\Automation\Requests\StoreAutomationRuleRequest;
use App\Domains\Automation\Requests\UpdateAutomationRuleRequest;
use App\Domains\Automation\Resources\AutomationLogResource;
use App\Domains\Automation\Resources\AutomationRuleResource;
use App\Domains\Automation\Services\AutomationRuleService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationRuleController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AutomationRuleService $ruleService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', AutomationRule::class);

        return ApiResponse::success($this->ruleService->dashboard());
    }

    public function catalog(): JsonResponse
    {
        $this->authorize('viewAny', AutomationRule::class);

        return ApiResponse::success([
            'catalog' => $this->ruleService->catalog(),
        ]);
    }

    public function index(IndexAutomationRuleRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AutomationRule::class);
        $paginator = $this->ruleService->paginate($request->filters());

        return ApiResponse::success([
            'rules' => [
                'items' => AutomationRuleResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            'catalog' => $this->ruleService->catalog(),
        ]);
    }

    public function show(string $rule): JsonResponse
    {
        $model = $this->ruleService->find($rule);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'rule' => new AutomationRuleResource($model),
            'catalog' => $this->ruleService->catalog(),
        ]);
    }

    public function store(StoreAutomationRuleRequest $request): JsonResponse
    {
        $this->authorize('create', AutomationRule::class);

        /** @var User $actor */
        $actor = $request->user();
        $rule = $this->ruleService->create($request->validated(), $actor);

        return ApiResponse::success([
            'rule' => new AutomationRuleResource($rule),
        ], 'Automation rule created.', 201);
    }

    public function update(UpdateAutomationRuleRequest $request, string $rule): JsonResponse
    {
        $existing = $this->ruleService->find($rule);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->ruleService->update($rule, $request->validated(), $actor);

        return ApiResponse::success([
            'rule' => new AutomationRuleResource($updated),
        ], 'Automation rule updated.');
    }

    public function destroy(string $rule): JsonResponse
    {
        $existing = $this->ruleService->find($rule);
        $this->authorize('delete', $existing);
        $this->ruleService->delete($rule, request()->user());

        return ApiResponse::success(null, 'Automation rule deleted.');
    }

    public function toggle(Request $request, string $rule): JsonResponse
    {
        $existing = $this->ruleService->find($rule);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $enabled = $request->has('is_enabled') ? (bool) $request->boolean('is_enabled') : null;
        $updated = $this->ruleService->toggle($rule, $actor, $enabled);

        return ApiResponse::success([
            'rule' => new AutomationRuleResource($updated),
        ], $updated->is_enabled ? 'Automation rule enabled.' : 'Automation rule disabled.');
    }

    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AutomationRule::class);
        $paginator = $this->ruleService->paginateLogs($request->query());

        return ApiResponse::success([
            'statistics' => $this->ruleService->dashboard()['log_statistics'],
            'logs' => [
                'items' => AutomationLogResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function test(Request $request, string $rule): JsonResponse
    {
        $existing = $this->ruleService->find($rule);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->ruleService->testRun($rule, $request->input('context', []), $actor);

        return ApiResponse::success([
            'result' => $result,
        ], 'Automation test run completed.');
    }
}
