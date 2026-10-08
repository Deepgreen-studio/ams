<?php

namespace App\Domains\Customers\Requests;

use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Companies\Repositories\LocationRepository;
use App\Domains\Customers\Enums\CustomerApplicationOwnershipType;
use App\Domains\Customers\Enums\CustomerLegalBasis;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Repositories\IndustryRepository;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerRequest extends FormRequest
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
        $companyId = $this->resolveCompanyId();

        return [
            'company_id' => ['required', 'string'],
            'location_id' => ['nullable', 'string'],
            'customer_type' => ['required', Rule::in(CustomerType::values())],
            'reference' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('customers', 'reference')
                    ->where(fn ($query) => $query->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
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
            'legal_basis' => ['required', Rule::in(CustomerLegalBasis::values())],
            'processing_purpose' => ['required', 'string', 'max:500'],
            'retention_until' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(CustomerStatus::values())],
            'notes' => ['nullable', 'string', 'max:5000'],
            'application' => ['nullable', 'array'],
            'application.application_id' => ['required_with:application', 'string'],
            'application.application_environment_id' => ['nullable', 'string'],
            'application.environment_id' => ['nullable', 'string'],
            'application.ownership_type' => ['nullable', Rule::in(CustomerApplicationOwnershipType::values())],
            'application.platform' => ['nullable', Rule::in(ApplicationPlatform::values())],
            'application.status' => ['nullable', 'string', 'max:32'],
            'application.notes' => ['nullable', 'string', 'max:5000'],
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

            if ($type->requiresCompanyName() && blank($this->input('legal_name'))) {
                $validator->errors()->add('legal_name', 'Legal name is required for business and enterprise customers.');
            }

            $this->validateIndustrySelection($validator);
            $this->validateActiveCompany($validator);
            $this->validateLocation($validator);
        });
    }

    protected function validateLocation(Validator $validator, ?int $currentLocationId = null): void
    {
        if (blank($this->input('location_id'))) {
            return;
        }

        $location = app(LocationRepository::class)->findByIdentifier((string) $this->input('location_id'));
        $status = $location?->status instanceof CompanyStatus
            ? $location->status
            : CompanyStatus::tryFrom((string) $location?->status);
        $companyId = $this->resolveCompanyId();
        $sameLocation = $currentLocationId !== null && $location && (int) $location->id === $currentLocationId;

        if (! $location || ($companyId && (int) $location->company_id !== $companyId)) {
            $validator->errors()->add('location_id', 'Select an active location for this company.');

            return;
        }

        if (! $sameLocation && $status !== CompanyStatus::Active) {
            $validator->errors()->add('location_id', 'Select an active location.');
        }
    }

    protected function validateIndustrySelection(Validator $validator): void
    {
        $industries = app(IndustryRepository::class);
        $industry = filled($this->input('industry_id'))
            ? $industries->findByIdentifier((string) $this->input('industry_id'))
            : null;
        $subIndustry = filled($this->input('sub_industry_id'))
            ? $industries->findByIdentifier((string) $this->input('sub_industry_id'))
            : null;

        if (filled($this->input('industry_id')) && ! $industry) {
            $validator->errors()->add('industry_id', 'Selected industry is not available.');
        }

        if (filled($this->input('sub_industry_id')) && ! $subIndustry) {
            $validator->errors()->add('sub_industry_id', 'Selected sub-industry is not available.');
        }

        if ($industry && $subIndustry && (int) $subIndustry->parent_id !== (int) $industry->id) {
            $validator->errors()->add('sub_industry_id', 'Sub-industry must belong to the selected industry.');
        }

        if ($industry?->is_other && blank($this->input('industry_other'))) {
            $validator->errors()->add('industry_other', 'Describe the industry when Other is selected.');
        }
    }

    protected function validateActiveCompany(Validator $validator): void
    {
        if (blank($this->input('company_id'))) {
            return;
        }

        $company = app(CompanyRepository::class)->findByIdentifier((string) $this->input('company_id'));
        $status = $company?->status instanceof CompanyStatus
            ? $company->status
            : CompanyStatus::tryFrom((string) $company?->status);

        if ($status !== CompanyStatus::Active) {
            $validator->errors()->add('company_id', 'Select an active company.');
        }
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
