<?php

namespace App\Domains\Automation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationConditionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'field' => $this->field,
            'operator' => $this->operator?->value ?? $this->operator,
            'operator_label' => $this->operator?->label(),
            'value' => $this->value,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
