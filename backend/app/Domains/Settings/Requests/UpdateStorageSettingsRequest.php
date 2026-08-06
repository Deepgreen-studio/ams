<?php

namespace App\Domains\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageSettingsRequest extends FormRequest
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
            'default_disk' => ['sometimes', 'string', 'max:64'],
            'public_disk' => ['sometimes', 'string', 'max:64'],
            'private_disk' => ['sometimes', 'string', 'max:64'],
            'max_upload_kb' => ['sometimes', 'integer', 'min:64', 'max:102400'],
            'allowed_extensions' => ['sometimes', 'array'],
            'allowed_extensions.*' => ['string', 'max:16'],
            'cloud_provider' => ['sometimes', 'nullable', 'string', 'in:s3,gcs,azure'],
        ];
    }
}
