<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Repositories\CustomerRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = app(CustomerRepository::class)->findByIdentifier(
            (string) $this->route('customer'),
            withTrashed: true
        );

        $companyIdentifier = $this->input('company_id', $customer?->company_id);
        $companyId = $this->resolveCompanyId($companyIdentifier) ?? $customer?->company_id;

        return [
            'company_id' => ['sometimes', 'required', 'string'],
            'customer_type' => ['sometimes', 'required', Rule::in(CustomerType::values())],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->ignore($customer?->id)
                    ->where(fn ($query) => $query->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-\(\)]{7,30}$/'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'status' => ['sometimes', 'required', Rule::in(CustomerStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $customer = app(CustomerRepository::class)->findByIdentifier(
                (string) $this->route('customer'),
                withTrashed: true
            );

            $typeValue = $this->input('customer_type', $customer?->customer_type?->value);
            $type = $typeValue instanceof CustomerType
                ? $typeValue
                : CustomerType::tryFrom((string) $typeValue);

            if (! $type) {
                return;
            }

            $firstName = $this->has('first_name') ? $this->input('first_name') : $customer?->first_name;
            $lastName = $this->has('last_name') ? $this->input('last_name') : $customer?->last_name;
            $companyName = $this->has('company_name') ? $this->input('company_name') : $customer?->company_name;

            if ($type->requiresPersonName()) {
                if (blank($firstName)) {
                    $validator->errors()->add('first_name', 'First name is required for individual customers.');
                }

                if (blank($lastName)) {
                    $validator->errors()->add('last_name', 'Last name is required for individual customers.');
                }
            }

            if ($type->requiresCompanyName() && blank($companyName)) {
                $validator->errors()->add('company_name', 'Company name is required for business and enterprise customers.');
            }
        });
    }

    protected function resolveCompanyId(mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        return app(CompanyRepository::class)->findByIdentifier((string) $identifier)?->id;
    }
}
