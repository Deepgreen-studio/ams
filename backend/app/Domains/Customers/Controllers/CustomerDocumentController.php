<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerDocumentRequest;
use App\Domains\Customers\Requests\StoreCustomerDocumentRequest;
use App\Domains\Customers\Requests\UpdateCustomerDocumentRequest;
use App\Domains\Customers\Requests\UploadCustomerDocumentVersionRequest;
use App\Domains\Customers\Resources\CustomerDocumentCollection;
use App\Domains\Customers\Resources\CustomerDocumentResource;
use App\Domains\Customers\Services\CustomerDocumentService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDocumentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerDocumentService $documentService
    ) {}

    public function index(IndexCustomerDocumentRequest $request): JsonResponse
    {
        $this->authorize('viewDocuments', Customer::class);

        $result = $this->documentService->library($request->filters());

        return ApiResponse::success([
            'documents' => (new CustomerDocumentCollection($result['documents']))->resolve(),
            'statistics' => $result['statistics'],
            'folders' => $result['folders'],
        ]);
    }

    public function folders(Request $request): JsonResponse
    {
        $this->authorize('viewDocuments', Customer::class);

        $customer = $request->query('customer') ?? $request->query('customer_id');

        return ApiResponse::success([
            'folders' => $this->documentService->folders(
                is_string($customer) || is_numeric($customer) ? (string) $customer : null
            ),
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewDocuments', Customer::class);

        $customer = $request->query('customer') ?? $request->query('customer_id');

        return ApiResponse::success([
            'statistics' => $this->documentService->statistics(
                is_string($customer) || is_numeric($customer) ? (string) $customer : null
            ),
        ]);
    }

    public function store(StoreCustomerDocumentRequest $request): JsonResponse
    {
        $this->authorize('manageDocuments', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $file = $request->file('file');
        unset($validated['file']);

        $document = $this->documentService->upload($validated, $file, $actor);

        return ApiResponse::success([
            'document' => new CustomerDocumentResource($document),
        ], 'Document uploaded successfully.', 201);
    }

    public function show(string $document): JsonResponse
    {
        $model = $this->documentService->show($document);
        $this->authorize('viewDocument', $model);

        return ApiResponse::success([
            'document' => new CustomerDocumentResource($model),
        ]);
    }

    public function update(UpdateCustomerDocumentRequest $request, string $document): JsonResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('updateDocument', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->documentService->update($document, $request->validated(), $actor);

        return ApiResponse::success([
            'document' => new CustomerDocumentResource($updated),
        ], 'Document updated successfully.');
    }

    public function uploadVersion(UploadCustomerDocumentVersionRequest $request, string $document): JsonResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('updateDocument', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $file = $request->file('file');
        unset($validated['file']);

        $version = $this->documentService->uploadVersion($document, $file, $validated, $actor);

        return ApiResponse::success([
            'document' => new CustomerDocumentResource($version),
        ], 'Document version uploaded successfully.', 201);
    }

    public function versions(string $document): JsonResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('viewDocument', $existing);

        return ApiResponse::success([
            'versions' => CustomerDocumentResource::collection(
                $this->documentService->versions($document)
            )->resolve(),
        ]);
    }

    public function download(string $document): StreamedResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('viewDocument', $existing);

        return $this->documentService->download($document);
    }

    public function preview(string $document): StreamedResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('viewDocument', $existing);

        return $this->documentService->preview($document);
    }

    public function destroy(Request $request, string $document): JsonResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('deleteDocument', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->documentService->delete($document, $actor);

        return ApiResponse::success(null, 'Document archived successfully.');
    }

    public function restore(Request $request, string $document): JsonResponse
    {
        $existing = $this->documentService->find($document, withTrashed: true);
        $this->authorize('restoreDocument', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->documentService->restore($document, $actor);

        return ApiResponse::success([
            'document' => new CustomerDocumentResource($restored),
        ], 'Document restored successfully.');
    }

    public function timeline(Request $request, string $document): JsonResponse
    {
        $existing = $this->documentService->find($document);
        $this->authorize('viewDocument', $existing);

        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'timeline' => $this->documentService->timeline($document, $limit),
        ]);
    }
}
