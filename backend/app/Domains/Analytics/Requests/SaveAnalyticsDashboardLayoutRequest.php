<?php

namespace App\Domains\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnalyticsDashboardLayoutRequest extends FormRequest
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
            'layout' => ['nullable', 'array'],
            'layout.columns' => ['nullable', 'integer', 'min:1', 'max:24'],
            'layout.row_height' => ['nullable', 'integer', 'min:40', 'max:200'],
            'layout.gap' => ['nullable', 'integer', 'min:0', 'max:48'],
            'widgets' => ['required', 'array', 'min:1'],
            'widgets.*.uuid' => ['required', 'uuid'],
            'widgets.*.position_x' => ['required', 'integer', 'min:0', 'max:11'],
            'widgets.*.position_y' => ['required', 'integer', 'min:0', 'max:200'],
            'widgets.*.width' => ['required', 'integer', 'min:2', 'max:12'],
            'widgets.*.height' => ['required', 'integer', 'min:2', 'max:12'],
            'widgets.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'widgets.*.is_visible' => ['nullable', 'boolean'],
        ];
    }
}
