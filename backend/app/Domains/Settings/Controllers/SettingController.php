<?php

namespace App\Domains\Settings\Controllers;

use App\Domains\Settings\Models\SystemSetting;
use App\Domains\Settings\Requests\UpdateApiSettingsRequest;
use App\Domains\Settings\Requests\UpdateEmailSettingsRequest;
use App\Domains\Settings\Requests\UpdateGeneralSettingsRequest;
use App\Domains\Settings\Requests\UpdateQueueSettingsRequest;
use App\Domains\Settings\Requests\UpdateSecuritySettingsRequest;
use App\Domains\Settings\Requests\UpdateStorageSettingsRequest;
use App\Domains\Settings\Services\CacheService;
use App\Domains\Settings\Services\ConfigurationService;
use App\Domains\Settings\Services\QueueService;
use App\Domains\Settings\Services\SystemSettingService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SystemSettingService $settingService,
        private readonly ConfigurationService $configurationService,
        private readonly CacheService $cacheService,
        private readonly QueueService $queueService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->allGrouped(),
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        /** @var User $actor */
        $actor = $request->user();
        $settings = $this->settingService->updateGroup('general', $request->validated(), $actor, $request->ip());

        return ApiResponse::success(['settings' => $settings], 'General settings updated successfully.');
    }

    public function showEmail(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->getGroup('email'),
        ]);
    }

    public function updateEmail(UpdateEmailSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        /** @var User $actor */
        $actor = $request->user();
        $settings = $this->settingService->updateGroup('email', $request->validated(), $actor, $request->ip());

        return ApiResponse::success(['settings' => $settings], 'Email settings updated successfully.');
    }

    public function showStorage(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->getGroup('storage'),
        ]);
    }

    public function updateStorage(UpdateStorageSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        /** @var User $actor */
        $actor = $request->user();
        $settings = $this->settingService->updateGroup('storage', $request->validated(), $actor, $request->ip());

        return ApiResponse::success(['settings' => $settings], 'Storage settings updated successfully.');
    }

    public function showSecurity(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->getGroup('security'),
        ]);
    }

    public function updateSecurity(UpdateSecuritySettingsRequest $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        /** @var User $actor */
        $actor = $request->user();
        $settings = $this->settingService->updateGroup('security', $request->validated(), $actor, $request->ip());

        return ApiResponse::success(['settings' => $settings], 'Security settings updated successfully.');
    }

    public function showApi(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->getGroup('api'),
        ]);
    }

    public function updateApi(UpdateApiSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        /** @var User $actor */
        $actor = $request->user();
        $settings = $this->settingService->updateGroup('api', $request->validated(), $actor, $request->ip());

        return ApiResponse::success(['settings' => $settings], 'API settings updated successfully.');
    }

    public function showQueue(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->getGroup('queue'),
            'status' => $this->queueService->status(),
        ]);
    }

    public function updateQueue(UpdateQueueSettingsRequest $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);

        /** @var User $actor */
        $actor = $request->user();
        $settings = $this->settingService->updateGroup('queue', $request->validated(), $actor, $request->ip());

        return ApiResponse::success([
            'settings' => $settings,
            'status' => $this->queueService->status(),
        ], 'Queue settings updated successfully.');
    }

    public function showCache(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'settings' => $this->settingService->getGroup('cache'),
            'status' => $this->cacheService->status(),
        ]);
    }

    public function systemInfo(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        return ApiResponse::success([
            'system' => $this->settingService->systemInformation(),
        ]);
    }

    public function refreshConfiguration(Request $request): JsonResponse
    {
        $this->authorize('update', SystemSetting::class);
        $this->configurationService->refreshCache();

        return ApiResponse::success(null, 'Configuration cache refreshed successfully.');
    }
}
