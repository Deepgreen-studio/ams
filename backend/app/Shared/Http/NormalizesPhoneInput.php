<?php

namespace App\Shared\Http;

use App\Shared\Support\PhoneNumber;

trait NormalizesPhoneInput
{
    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach ($this->phoneFields() as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $merged[$field] = PhoneNumber::canonicalize($this->input($field));
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return list<string>
     */
    protected function phoneFields(): array
    {
        return ['phone'];
    }
}
