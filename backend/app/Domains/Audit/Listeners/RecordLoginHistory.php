<?php

namespace App\Domains\Audit\Listeners;

use App\Domains\Audit\Services\LoginHistoryService;
use App\Domains\Authentication\Events\UserLoggedIn;
use App\Domains\Authentication\Events\UserLoggedOut;

class RecordLoginHistory
{
    public function __construct(
        private readonly LoginHistoryService $loginHistoryService
    ) {}

    public function handleUserLoggedIn(UserLoggedIn $event): void
    {
        $this->loginHistoryService->recordLogin($event->user, $event->request, 'success');
    }

    public function handleUserLoggedOut(UserLoggedOut $event): void
    {
        $this->loginHistoryService->recordLogout($event->user, $event->request);
    }
}
