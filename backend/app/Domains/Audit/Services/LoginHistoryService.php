<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Repositories\LoginHistoryRepository;
use App\Domains\Users\Models\UserLoginHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginHistoryService
{
    public function __construct(
        private readonly LoginHistoryRepository $loginHistoryRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->loginHistoryRepository->paginateFiltered($filters);
    }

    public function recordFailedLogin(?User $user, Request $request): UserLoginHistory
    {
        return $this->recordLoginForUser($user?->id, $request, 'failed');
    }

    public function recordLogin(User $user, Request $request, string $status = 'success'): UserLoginHistory
    {
        return $this->recordLoginForUser($user->id, $request, $status);
    }

    protected function recordLoginForUser(?int $userId, Request $request, string $status): UserLoginHistory
    {
        $agent = (string) $request->userAgent();
        $parsed = $this->parseUserAgent($agent);

        /** @var UserLoginHistory $history */
        $history = $this->loginHistoryRepository->create([
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $agent,
            'device' => $parsed['device'],
            'platform' => $parsed['platform'],
            'operating_system' => $parsed['operating_system'],
            'browser' => $parsed['browser'],
            'status' => $status,
            'session_id' => (string) Str::uuid(),
            'logged_in_at' => now(),
        ]);

        return $history;
    }

    public function recordLogout(User $user, ?Request $request = null): ?UserLoginHistory
    {
        $history = $this->loginHistoryRepository->findOpenSession($user->id);
        if (! $history) {
            return null;
        }

        $history->logout_at = now();
        $history->save();

        return $history->refresh();
    }

    /**
     * @return array{device: string, platform: string, operating_system: string, browser: string}
     */
    protected function parseUserAgent(string $userAgent): array
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $device = 'Desktop';

        if (preg_match('/Edg\/[\d.]+/', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\/[\d.]+/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/', $userAgent) && ! str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        }

        if (preg_match('/Windows NT [\d.]+/', $userAgent)) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
            $device = 'Mobile';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
            $device = str_contains($userAgent, 'iPad') ? 'Tablet' : 'Mobile';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        }

        return [
            'device' => $device,
            'platform' => $os,
            'operating_system' => $os,
            'browser' => $browser,
        ];
    }
}
