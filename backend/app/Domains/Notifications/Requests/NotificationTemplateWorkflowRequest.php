<?php

namespace App\Domains\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationTemplateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
