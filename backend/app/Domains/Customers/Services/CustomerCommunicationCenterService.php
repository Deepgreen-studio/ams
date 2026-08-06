<?php

namespace App\Domains\Customers\Services;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Repositories\CustomerCommunicationRepository;
use App\Domains\Customers\Repositories\CustomerNoteRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Customers\Repositories\CustomerTaskRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Aggregates notes, tasks, and communications into hub timelines.
 */
class CustomerCommunicationCenterService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerNoteRepository $noteRepository,
        private readonly CustomerTaskRepository $taskRepository,
        private readonly CustomerCommunicationRepository $communicationRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(string $customerIdentifier): array
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);

        return [
            'customer' => [
                'uuid' => $customer->uuid,
                'display_name' => $customer->display_name,
                'email' => $customer->email,
            ],
            'statistics' => [
                'notes' => $this->noteRepository->statistics($customer->id),
                'tasks' => $this->taskRepository->statistics($customer->id),
                'communications' => $this->communicationRepository->statistics($customer->id),
            ],
            'timeline' => $this->communicationTimeline($customer, 40),
            'activity' => $this->activityTimeline($customer, 40),
            'reminders' => $this->taskRepository->reminders($customer->id, now()->startOfDay(), now()->addDays(30)),
        ];
    }

    /**
     * Combined communication timeline (notes + tasks + emails/calls).
     *
     * @return list<array<string, mixed>>
     */
    public function communicationTimeline(Customer|string $customer, int $limit = 50): array
    {
        $model = $customer instanceof Customer
            ? $customer
            : $this->customerRepository->findByIdentifierOrFail($customer);

        $notes = $this->noteRepository->filteredQuery(['customer_id' => $model->id])
            ->limit($limit)
            ->get()
            ->map(fn ($note): array => [
                'source' => 'note',
                'uuid' => $note->uuid,
                'type' => $note->note_type?->value ?? $note->note_type,
                'title' => $note->title ?: ($note->note_type?->label() ?? 'Note'),
                'summary' => str($note->body)->limit(160)->toString(),
                'status' => $note->status?->value ?? $note->status,
                'occurred_at' => $note->occurred_at ?? $note->created_at,
            ]);

        $tasks = $this->taskRepository->filteredQuery(['customer_id' => $model->id])
            ->limit($limit)
            ->get()
            ->map(fn ($task): array => [
                'source' => 'task',
                'uuid' => $task->uuid,
                'type' => 'task',
                'title' => $task->title,
                'summary' => $task->description ? str($task->description)->limit(160)->toString() : null,
                'status' => $task->status?->value ?? $task->status,
                'occurred_at' => $task->due_at ?? $task->created_at,
            ]);

        $comms = $this->communicationRepository->filteredQuery(['customer_id' => $model->id])
            ->limit($limit)
            ->get()
            ->map(fn ($item): array => [
                'source' => 'communication',
                'uuid' => $item->uuid,
                'type' => $item->type?->value ?? $item->type,
                'title' => $item->subject ?: ($item->type?->label() ?? 'Communication'),
                'summary' => $item->body ? str($item->body)->limit(160)->toString() : null,
                'status' => $item->status?->value ?? $item->status,
                'occurred_at' => $item->occurred_at,
            ]);

        return $notes->concat($tasks)->concat($comms)
            ->sortByDesc(fn (array $row) => Carbon::parse((string) $row['occurred_at'])->timestamp)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Spatie activity stream across communication subjects for this customer.
     *
     * @return list<array<string, mixed>>
     */
    public function activityTimeline(Customer|string $customer, int $limit = 50): array
    {
        $model = $customer instanceof Customer
            ? $customer
            : $this->customerRepository->findByIdentifierOrFail($customer);

        $noteIds = $this->noteRepository->filteredQuery(['customer_id' => $model->id])->pluck('id');
        $taskIds = $this->taskRepository->filteredQuery(['customer_id' => $model->id])->pluck('id');
        $commIds = $this->communicationRepository->filteredQuery(['customer_id' => $model->id])->pluck('id');

        return Activity::query()
            ->where(function ($query) use ($noteIds, $taskIds, $commIds): void {
                $query->where(function ($builder) use ($noteIds): void {
                    $builder->where('subject_type', \App\Domains\Customers\Models\CustomerNote::class)
                        ->whereIn('subject_id', $noteIds);
                })->orWhere(function ($builder) use ($taskIds): void {
                    $builder->where('subject_type', \App\Domains\Customers\Models\CustomerTask::class)
                        ->whereIn('subject_id', $taskIds);
                })->orWhere(function ($builder) use ($commIds): void {
                    $builder->where('subject_type', \App\Domains\Customers\Models\CustomerCommunication::class)
                        ->whereIn('subject_id', $commIds);
                });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Activity $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'event' => $item->properties['event'] ?? null,
                'log_name' => $item->log_name,
                'subject_type' => class_basename((string) $item->subject_type),
                'created_at' => $item->created_at,
            ])
            ->all();
    }

    /**
     * @return Collection<int, \App\Domains\Customers\Models\CustomerTask>
     */
    public function reminderCalendar(string $customerIdentifier, ?string $from = null, ?string $to = null): Collection
    {
        $customer = $this->customerRepository->findByIdentifierOrFail($customerIdentifier);

        return $this->taskRepository->reminders(
            $customer->id,
            $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth(),
            $to ? Carbon::parse($to)->endOfDay() : now()->endOfMonth()
        );
    }
}
