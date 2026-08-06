<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerRequest extends FormRequest
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
        $companyId = $this->resolveCompanyId();

        return [
            'company_id' => ['required', 'string'],
            'customer_type' => ['required', Rule::in(CustomerType::values())],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($query) => $query->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-\(\)]{7,30}$/'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'status' => ['nullable', Rule::in(CustomerStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = CustomerType::tryFrom((string) $this->input('customer_type'));

            if (! $type) {
                return;
            }

            if ($type->requiresPersonName()) {
                if (blank($this->input('first_name'))) {
                    $validator->errors()->add('first_name', 'First name is required for individual customers.');
                }

                if (blank($this->input('last_name'))) {
                    $validator->errors()->add('last_name', 'Last name is required for individual customers.');
                }
            }

            if ($type->requiresCompanyName() && blank($this->input('company_name'))) {
                $validator->errors()->add('company_name', 'Company name is required for business and enterprise customers.');
            }
        });
    }

    protected function resolveCompanyId(): ?int
    {
        $identifier = $this->input('company_id');

        if (blank($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        return app(CompanyRepository::class)->findByIdentifier((string) $identifier)?->id;
    }
}
