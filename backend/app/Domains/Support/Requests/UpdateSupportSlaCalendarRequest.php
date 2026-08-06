<?php

namespace App\Domains\Support\Requests;

class UpdateSupportSlaCalendarRequest extends StoreSupportSlaCalendarRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['business_hours'] = ['sometimes', 'required', 'array'];

        return $rules;
    }
}
