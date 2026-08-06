<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\UserConsent;
use App\Domains\Compliance\Requests\SaveConsentPreferencesRequest;
use App\Domains\Compliance\Requests\StoreConsentTypeRequest;
use App\Domains\Compliance\Requests\StoreUserConsentRequest;
use App\Domains\Compliance\Requests\WithdrawUserConsentRequest;
use App\Domains\Compliance\Resources\ConsentHistoryCollection;
use App\Domains\Compliance\Resources\ConsentHistoryResource;
use App\Domains\Compliance\Resources\ConsentTypeResource;
use App\Domains\Compliance\Resources\UserConsentCollection;
use App\Domains\Compliance\Resources\UserConsentResource;
use App\Domains\Compliance\Services\ConsentService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConsentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConsentService $consentService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserConsent::class);

        $result = $this->consentService->dashboard($request->query('company'));

        return ApiResponse::success([
            'statistics' => $result['statistics'],
            'recent' => UserConsentResource::collection($result['recent'])->resolve(),
            'types' => ConsentTypeResource::collection($result['types'])->resolve(),
        ]);
    }

    public function types(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ConsentType::class);

        $types = $this->consentService->listTypes($request->only([
            'company',
            'company_id',
            'channel',
            'search',
            'is_active',
            'all',
            'per_page',
            'page',
        ]));

        if ($types instanceof Collection) {
            return ApiResponse::success([
                'consent_types' => ConsentTypeResource::collection($types)->resolve(),
            ]);
        }

        return ApiResponse::success([
            'consent_types' => [
                'items' => ConsentTypeResource::collection($types->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $types->currentPage(),
                    'from' => $types->firstItem(),
                    'last_page' => $types->lastPage(),
                    'per_page' => $types->perPage(),
                    'to' => $types->lastItem(),
                    'total' => $types->total(),
                ],
            ],
        ]);
    }

    public function storeType(StoreConsentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', ConsentType::class);

        /** @var User $actor */
        $actor = $request->user();
        $type = $this->consentService->createType($request->validated(), $actor);

        return ApiResponse::success([
            'consent_type' => new ConsentTypeResource($type),
        ], 'Consent type created successfully.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserConsent::class);

        $consents = $this->consentService->listConsents($request->only([
            'search',
            'status',
            'granted',
            'source',
            'channel',
            'company',
            'company_id',
            'consent_type',
            'consent_type_id',
            'user',
            'user_id',
            'customer',
            'customer_id',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'consents' => (new UserConsentCollection($consents))->resolve(),
        ]);
    }

    public function store(StoreUserConsentRequest $request): JsonResponse
    {
        $this->authorize('create', UserConsent::class);

        /** @var User $actor */
        $actor = $request->user();
        $consent = $this->consentService->grantOrUpdate(
            $request->validated(),
            $actor,
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success([
            'consent' => new UserConsentResource($consent),
        ], 'Consent recorded successfully.', 201);
    }

    public function show(string $consent): JsonResponse
    {
        $model = $this->consentService->show($consent);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'consent' => new UserConsentResource($model),
        ]);
    }

    public function withdraw(WithdrawUserConsentRequest $request, string $consent): JsonResponse
    {
        $existing = $this->consentService->show($consent);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->consentService->withdraw(
            $consent,
            $request->validated(),
            $actor,
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success([
            'consent' => new UserConsentResource($updated),
        ], 'Consent withdrawn successfully.');
    }

    public function timeline(string $consent): JsonResponse
    {
        $existing = $this->consentService->show($consent);
        $this->authorize('view', $existing);

        $timeline = $this->consentService->timeline($consent);

        return ApiResponse::success([
            'timeline' => ConsentHistoryResource::collection($timeline)->resolve(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserConsent::class);

        $history = $this->consentService->listHistory($request->only([
            'company',
            'company_id',
            'consent',
            'consent_type_id',
            'action',
            'search',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'history' => (new ConsentHistoryCollection($history))->resolve(),
        ]);
    }

    public function preferenceCenter(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserConsent::class);

        $result = $this->consentService->preferenceCenter($request->only([
            'company_id',
            'user_id',
            'customer_id',
            'subject_email',
            'subject_name',
        ]));

        $preferences = collect($result['preferences'])->map(function (array $row) {
            return [
                'consent_type' => (new ConsentTypeResource($row['consent_type']))->resolve(),
                'consent' => $row['consent']
                    ? (new UserConsentResource($row['consent']))->resolve()
                    : null,
                'granted' => $row['granted'],
                'status' => $row['status'],
                'consent_version' => $row['consent_version'],
                'consented_at' => $row['consented_at'],
                'withdrawn_at' => $row['withdrawn_at'],
            ];
        })->values();

        return ApiResponse::success([
            'company' => [
                'id' => $result['company']->id,
                'uuid' => $result['company']->uuid,
                'company_name' => $result['company']->company_name,
            ],
            'subject' => [
                'user' => $result['subject']['user'] ? [
                    'id' => $result['subject']['user']->id,
                    'uuid' => $result['subject']['user']->uuid,
                    'full_name' => $result['subject']['user']->full_name,
                    'email' => $result['subject']['user']->email,
                ] : null,
                'customer' => $result['subject']['customer'] ? [
                    'id' => $result['subject']['customer']->id,
                    'uuid' => $result['subject']['customer']->uuid,
                    'display_name' => $result['subject']['customer']->display_name,
                    'email' => $result['subject']['customer']->email,
                ] : null,
                'subject_email' => $result['subject']['subject_email'],
                'subject_name' => $result['subject']['subject_name'],
            ],
            'preferences' => $preferences,
        ]);
    }

    public function savePreferences(SaveConsentPreferencesRequest $request): JsonResponse
    {
        $this->authorize('create', UserConsent::class);

        /** @var User $actor */
        $actor = $request->user();
        $consents = $this->consentService->savePreferences(
            $request->validated(),
            $actor,
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success([
            'consents' => UserConsentResource::collection(collect($consents))->resolve(),
        ], 'Consent preferences saved successfully.');
    }
}
