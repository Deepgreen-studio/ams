<?php

namespace App\Shared\Helpers;

class ValidationFormatter
{
    /**
     * Format Laravel validation errors into a consistent API structure.
     *
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    public static function format(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            $formatted[$field] = array_values($messages);
        }

        return $formatted;
    }
}
