<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Requests\IndexCustomerContactRequest;
use App\Domains\Customers\Requests\StoreCustomerContactRequest;
use App\Domains\Customers\Requests\UpdateCustomerContactRequest;
use App\Domains\Customers\Resources\CustomerContactCollection;
use App\Domains\Customers\Resources\CustomerContactResource;
use App\Domains\Customers\Services\CustomerContactImportService;
use App\Domains\Customers\Services\CustomerContactService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerContactController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerContactService $customerContactService,
        private readonly CustomerContactImportService $customerContactImportService,
    ) {}

    public function index(IndexCustomerContactRequest $request): JsonResponse
    {
        $this->authorize('viewContacts', Customer::class);

        $contacts = $this->customerContactService->list($request->filters());

        return ApiResponse::success([
            'contacts' => (new CustomerContactCollection($contacts))->resolve(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('exportContacts', Customer::class);

        $customer = (string) $request->query('customer', '');
        $format = (string) $request->query('format', 'csv');

        if ($customer === '') {
            abort(422, 'A customer is required to export contacts.');
        }

        if (! in_array($format, ['csv', 'xlsx'], true)) {
            abort(422, 'Export format must be csv or xlsx.');
        }

        return $this->customerContactImportService->export($customer, $format);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('manageContacts', Customer::class);

        $validated = $request->validate([
            'customer_id' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $report = $this->customerContactImportService->import(
            $request->file('file'),
            $validated['customer_id'],
            $request->boolean('update_existing'),
            $actor
        );

        return ApiResponse::success([
            'import' => $report,
        ], 'Contact import completed.');
    }

    public function store(StoreCustomerContactRequest $request): JsonResponse
    {
        $this->authorize('manageContacts', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $contact = $this->customerContactService->create($request->validated(), $actor);

        return ApiResponse::success([
            'contact' => new CustomerContactResource($contact),
        ], 'Customer contact created successfully.', 201);
    }

    public function show(string $contact): JsonResponse
    {
        $model = $this->customerContactService->show($contact);
        $this->authorize('viewContact', $model);

        return ApiResponse::success([
            'contact' => new CustomerContactResource($model),
        ]);
    }

    public function update(UpdateCustomerContactRequest $request, string $contact): JsonResponse
    {
        $existing = $this->customerContactService->find($contact);
        $this->authorize('updateContact', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->customerContactService->update($contact, $request->validated(), $actor);

        return ApiResponse::success([
            'contact' => new CustomerContactResource($updated),
        ], 'Customer contact updated successfully.');
    }

    public function destroy(Request $request, string $contact): JsonResponse
    {
        $existing = $this->customerContactService->find($contact);
        $this->authorize('deleteContact', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->customerContactService->delete($contact, $actor);

        return ApiResponse::success(null, 'Customer contact archived successfully.');
    }

    public function restore(Request $request, string $contact): JsonResponse
    {
        $existing = $this->customerContactService->find($contact, withTrashed: true);
        $this->authorize('restoreContact', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->customerContactService->restore($contact, $actor);

        return ApiResponse::success([
            'contact' => new CustomerContactResource($restored),
        ], 'Customer contact restored successfully.');
    }

    public function timeline(Request $request, string $contact): JsonResponse
    {
        $existing = $this->customerContactService->find($contact);
        $this->authorize('viewContact', $existing);

        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return ApiResponse::success([
            'timeline' => $this->customerContactService->timeline($contact, $limit),
        ]);
    }
}
