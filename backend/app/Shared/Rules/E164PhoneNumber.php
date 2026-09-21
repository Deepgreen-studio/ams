<?php

namespace App\Shared\Rules;

use App\Shared\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class E164PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! PhoneNumber::isE164($value)) {
            $fail('The :attribute must be a valid international phone number with a country code.');
        }
    }
}
