<?php

namespace App\Domains\Companies\Requests;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Shared\Http\NormalizesPhoneInput;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'display name',
            'registration_number' => 'company code',
        ];
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:100', Rule::unique('companies', 'registration_number')->whereNull('deleted_at')],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => $this->requiredPhoneRules(),
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country' => ['required', 'string', 'max:100'],
            'timezone' => ['nullable', 'timezone:all'],
            'language' => ['nullable', 'string', 'max:16'],
            'currency' => ['required', 'string', 'size:3'],
            'date_format' => ['nullable', 'string', 'max:32'],
            'time_format' => ['nullable', 'string', 'max:32'],
            'business_hours' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(CompanyStatus::values())],
        ];
    }

    /**
     * @return list<mixed>
     */
    private function requiredPhoneRules(): array
    {
        return array_merge(
            ['required'],
            array_values(array_filter(
                PhoneNumber::inputRules(),
                static fn (mixed $rule): bool => $rule !== 'nullable',
            )),
        );
    }
}
