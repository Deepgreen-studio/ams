<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Events\ActivityLogged;
use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Audit\Repositories\ActivityRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogService
{
    public function __construct(
        private readonly ActivityRepository $activityRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->activityRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): ActivityLog
    {
        return $this->activityRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $module,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $event = null
    ): ActivityLog {
        $logger = activity($module);

        if ($actor) {
            $logger->causedBy($actor);
        }

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($event) {
            $logger->event($event);
        }

        /** @var ActivityLog $activity */
        $activity = $logger->withProperties($properties)->log($description);

        event(new ActivityLogged($activity));

        return $activity;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters = []): StreamedResponse
    {
        $rows = $this->activityRepository->export($filters);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Module', 'Event', 'Description', 'User', 'Created At']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->log_name,
                    $row->event,
                    $row->description,
                    $row->causer?->email ?? $row->causer?->full_name,
                    optional($row->created_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'activity-logs-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
