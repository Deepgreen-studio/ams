<?php

namespace App\Domains\Support\Requests;

class UpdateKnowledgeTagRequest extends StoreKnowledgeTagRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'required', 'string', 'max:100'];

        return $rules;
    }
}
