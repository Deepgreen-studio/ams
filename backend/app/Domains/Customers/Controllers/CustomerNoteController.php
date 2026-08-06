<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerNoteRequest;
use App\Domains\Customers\Requests\StoreCustomerNoteRequest;
use App\Domains\Customers\Requests\UpdateCustomerNoteRequest;
use App\Domains\Customers\Resources\CustomerNoteCollection;
use App\Domains\Customers\Resources\CustomerNoteResource;
use App\Domains\Customers\Services\CustomerNoteService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNoteController
{
    use AuthorizesRequests;

    public function __construct(private readonly CustomerNoteService $noteService) {}

    public function index(IndexCustomerNoteRequest $request): JsonResponse
    {
        $this->authorize('viewCommunications', Customer::class);
        $result = $this->noteService->list($request->filters());

        return ApiResponse::success([
            'notes' => (new CustomerNoteCollection($result['notes']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function store(StoreCustomerNoteRequest $request): JsonResponse
    {
        $this->authorize('manageCommunications', Customer::class);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'note' => new CustomerNoteResource($this->noteService->create($request->validated(), $actor)),
        ], 'Note created successfully.', 201);
    }

    public function show(string $note): JsonResponse
    {
        $model = $this->noteService->show($note);
        $this->authorize('viewCommunication', $model);

        return ApiResponse::success(['note' => new CustomerNoteResource($model)]);
    }

    public function update(UpdateCustomerNoteRequest $request, string $note): JsonResponse
    {
        $existing = $this->noteService->find($note);
        $this->authorize('updateCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'note' => new CustomerNoteResource($this->noteService->update($note, $request->validated(), $actor)),
        ], 'Note updated successfully.');
    }

    public function destroy(Request $request, string $note): JsonResponse
    {
        $existing = $this->noteService->find($note);
        $this->authorize('deleteCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();
        $this->noteService->delete($note, $actor);

        return ApiResponse::success(null, 'Note archived successfully.');
    }

    public function restore(Request $request, string $note): JsonResponse
    {
        $existing = $this->noteService->find($note, withTrashed: true);
        $this->authorize('restoreCommunication', $existing);
        /** @var User $actor */
        $actor = $request->user();

        return ApiResponse::success([
            'note' => new CustomerNoteResource($this->noteService->restore($note, $actor)),
        ], 'Note restored successfully.');
    }
}
