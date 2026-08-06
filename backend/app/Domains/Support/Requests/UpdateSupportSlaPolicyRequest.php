<?php

namespace App\Domains\Support\Requests;

class UpdateSupportSlaPolicyRequest extends StoreSupportSlaPolicyRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['response_target_minutes'] = ['sometimes', 'required', 'integer', 'min:1', 'max:525600'];
        $rules['resolution_target_minutes'] = ['sometimes', 'required', 'integer', 'min:1', 'max:525600'];

        return $rules;
    }
}
