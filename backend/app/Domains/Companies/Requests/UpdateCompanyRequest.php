<?php

namespace App\Domains\Companies\Requests;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Repositories\CompanyRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = app(CompanyRepository::class)->findByIdentifier((string) $this->route('company'), withTrashed: true);

        return [
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('companies', 'registration_number')->ignore($company?->id)->whereNull('deleted_at'),
            ],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-\(\)]{7,30}$/'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'currency' => ['nullable', 'string', 'size:3'],
            'date_format' => ['nullable', 'string', 'max:32'],
            'time_format' => ['nullable', 'string', 'max:32'],
            'business_hours' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'required', Rule::in(CompanyStatus::values())],
        ];
    }
}
