<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\LocationRepository;
use App\Domains\Customers\Enums\CustomerLegalBasis;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Domains\Customers\Repositories\IndustryRepository;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    protected function phoneFields(): array
    {
        return ['phone', 'primary_contact_phone'];
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach ($this->phoneFields() as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $merged[$field] = PhoneNumber::canonicalize($this->input($field));
        }

        if ($this->filled('organization_name') && blank($this->input('legal_name'))) {
            $merged['legal_name'] = $this->input('organization_name');
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
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
            'location_id' => ['nullable', 'string'],
            'customer_type' => ['sometimes', 'required', Rule::in(CustomerType::values())],
            'reference' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('customers', 'reference')
                    ->ignore($customer?->id)
                    ->where(fn ($query) => $query->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->ignore($customer?->id)
                    ->where(fn ($query) => $query->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'phone' => PhoneNumber::inputRules(),
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'primary_contact_phone' => PhoneNumber::inputRules(),
            'primary_contact_title' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'industry_id' => ['nullable', 'string'],
            'sub_industry_id' => ['nullable', 'string'],
            'industry_other' => ['nullable', 'string', 'max:120'],
            'legal_basis' => ['sometimes', 'required', Rule::in(CustomerLegalBasis::values())],
            'processing_purpose' => ['sometimes', 'required', 'string', 'max:500'],
            'retention_until' => ['nullable', 'date'],
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
            $legalName = $this->has('legal_name') ? $this->input('legal_name') : $customer?->legal_name;

            if ($type->requiresPersonName()) {
                if (blank($firstName)) {
                    $validator->errors()->add('first_name', 'First name is required for individual customers.');
                }

                if (blank($lastName)) {
                    $validator->errors()->add('last_name', 'Last name is required for individual customers.');
                }
            }

            if ($type->requiresCompanyName() && blank($legalName)) {
                $validator->errors()->add('legal_name', 'Legal name is required for business and enterprise customers.');
            }

            if ($this->filled('company_id')) {
                $company = app(CompanyRepository::class)->findByIdentifier((string) $this->input('company_id'));
                $status = $company?->status instanceof CompanyStatus
                    ? $company->status
                    : CompanyStatus::tryFrom((string) $company?->status);
                $sameCompany = $customer && $company && (int) $company->id === (int) $customer->company_id;

                if (! $sameCompany && $status !== CompanyStatus::Active) {
                    $validator->errors()->add('company_id', 'Select an active company.');
                }
            }

            if ($this->exists('location_id') && filled($this->input('location_id'))) {
                $location = app(LocationRepository::class)->findByIdentifier((string) $this->input('location_id'));
                $status = $location?->status instanceof CompanyStatus
                    ? $location->status
                    : CompanyStatus::tryFrom((string) $location?->status);
                $sameLocation = $customer && $location && (int) $location->id === (int) $customer->location_id;
                $companyId = $this->resolveCompanyId($this->input('company_id', $customer?->company_id)) ?? $customer?->company_id;

                if (! $location || ($companyId && (int) $location->company_id !== (int) $companyId)) {
                    $validator->errors()->add('location_id', 'Select an active location for this company.');
                } elseif (! $sameLocation && $status !== CompanyStatus::Active) {
                    $validator->errors()->add('location_id', 'Select an active location.');
                }
            }

            if ($this->exists('industry_id') || $this->exists('sub_industry_id') || $this->exists('industry_other')) {
                $industries = app(IndustryRepository::class);
                $industry = filled($this->input('industry_id'))
                    ? $industries->findByIdentifier((string) $this->input('industry_id'))
                    : null;

                if ($this->filled('industry_id') && ! $industry) {
                    $validator->errors()->add('industry_id', 'Selected industry is not available.');
                }

                if ($industry?->is_other && blank($this->input('industry_other', $customer?->industry_other))) {
                    $validator->errors()->add('industry_other', 'Describe the industry when Other is selected.');
                }
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
