<?php

namespace App\Domains\Support\Requests;

class UpdateSupportSlaHolidayRequest extends StoreSupportSlaHolidayRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['holiday_date'] = ['sometimes', 'required', 'date'];

        return $rules;
    }
}
