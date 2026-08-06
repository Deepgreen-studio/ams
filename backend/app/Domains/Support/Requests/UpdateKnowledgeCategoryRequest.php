<?php

namespace App\Domains\Support\Requests;

class UpdateKnowledgeCategoryRequest extends StoreKnowledgeCategoryRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];

        return $rules;
    }
}
