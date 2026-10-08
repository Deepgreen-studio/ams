<?php

namespace App\Domains\Customers\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Repositories\IndustryRepository;
use App\Domains\Customers\Requests\IndexCustomerRequest;
use App\Domains\Customers\Requests\StoreCustomerRequest;
use App\Domains\Customers\Requests\UpdateCustomerRequest;
use App\Domains\Customers\Resources\CustomerCollection;
use App\Domains\Customers\Resources\CustomerResource;
use App\Domains\Customers\Resources\IndustryResource;
use App\Domains\Customers\Services\CustomerConsoleService;
use App\Domains\Customers\Services\CustomerImportService;
use App\Domains\Customers\Services\CustomerService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerConsoleService $customerConsoleService,
        private readonly IndustryRepository $industryRepository,
        private readonly CustomerImportService $customerImportService,
    ) {}

    public function index(IndexCustomerRequest $request): JsonResponse
    {
        if ($request->input('trashed') === 'only') {
            $this->authorize('viewTrash', Customer::class);
        } else {
            $this->authorize('viewAny', Customer::class);
        }

        $result = $this->customerService->list($request->filters());

        return ApiResponse::success([
            'customers' => (new CustomerCollection($result['customers']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function export(IndexCustomerRequest $request): StreamedResponse
    {
        $this->authorize('export', Customer::class);

        $format = (string) $request->query('format', 'csv');

        if (! in_array($format, ['csv', 'xlsx'], true)) {
            abort(422, 'Export format must be csv or xlsx.');
        }

        return $this->customerImportService->export($request->filters(), $format);
    }

    public function example(Request $request): StreamedResponse
    {
        $this->authorize('import', Customer::class);

        $format = (string) $request->query('format', 'csv');

        if (! in_array($format, ['csv', 'xlsx'], true)) {
            abort(422, 'Example format must be csv or xlsx.');
        }

        $company = $request->query('company');

        return $this->customerImportService->example(is_string($company) ? $company : null, $format);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('import', Customer::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'company_id' => ['nullable', 'string'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $report = $this->customerImportService->import(
            $request->file('file'),
            $request->input('company_id'),
            $request->boolean('update_existing'),
            $actor
        );

        return ApiResponse::success([
            'import' => $report,
        ], 'Customer import completed.');
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        /** @var User $actor */
        $actor = $request->user();
        $customer = $this->customerService->create($request->validated(), $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($customer),
        ], 'Customer created successfully.', 201);
    }

    public function show(string $customer): JsonResponse
    {
        $model = $this->customerService->show($customer);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'customer' => new CustomerResource($model),
        ]);
    }

    public function update(UpdateCustomerRequest $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->customerService->update($customer, $request->validated(), $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($updated),
        ], 'Customer updated successfully.');
    }

    public function destroy(Request $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->customerService->delete($customer, $actor);

        return ApiResponse::success(null, 'Customer archived successfully.');
    }

    public function restore(Request $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->customerService->restore($customer, $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($restored),
        ], 'Customer restored successfully.');
    }

    public function forceDelete(Request $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer, withTrashed: true);
        $this->authorize('forceDelete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->customerService->forceDelete($customer, $actor);

        return ApiResponse::success(null, 'Customer permanently deleted.');
    }

    public function industries(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        return ApiResponse::success([
            'industries' => IndustryResource::collection($this->industryRepository->tree())->resolve(),
        ]);
    }

    public function console(string $customer): JsonResponse
    {
        $model = $this->customerService->show($customer);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'customer' => new CustomerResource($model),
            'console' => $this->customerConsoleService->show($model),
        ]);
    }

    public function anonymize(Request $request, string $customer): JsonResponse
    {
        $existing = $this->customerService->find($customer);
        $this->authorize('anonymize', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->customerService->anonymize($customer, $actor);

        return ApiResponse::success([
            'customer' => new CustomerResource($updated),
        ], 'Customer personal data anonymized.');
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $company = $request->query('company') ?? $request->query('company_id');

        return ApiResponse::success([
            'statistics' => $this->customerService->statistics(
                is_string($company) || is_numeric($company) ? (string) $company : null
            ),
        ]);
    }
}
