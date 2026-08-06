<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Events\SystemEventCreated;
use App\Domains\Audit\Models\SystemEvent;
use App\Domains\Audit\Repositories\EventRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SystemEventService
{
    public function __construct(
        private readonly EventRepository $eventRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->eventRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): SystemEvent
    {
        return $this->eventRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function record(string $event, string $module = 'system', ?array $payload = null, string $level = 'info'): SystemEvent
    {
        /** @var SystemEvent $systemEvent */
        $systemEvent = $this->eventRepository->create([
            'event' => $event,
            'module' => $module,
            'level' => $level,
            'payload' => $payload,
        ]);

        event(new SystemEventCreated($systemEvent));

        return $systemEvent;
    }
}
