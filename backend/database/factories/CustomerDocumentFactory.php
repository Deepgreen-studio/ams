<?php

namespace Database\Factories;

use App\Domains\Customers\Enums\CustomerDocumentCategory;
use App\Domains\Customers\Enums\CustomerDocumentStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerDocument>
 */
class CustomerDocumentFactory extends Factory
{
    protected $model = CustomerDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'png']);
        $group = (string) Str::uuid();

        return [
            'uuid' => (string) Str::uuid(),
            'document_group_uuid' => $group,
            'version' => 1,
            'is_current' => true,
            'name' => fake()->words(3, true).' Document',
            'category' => fake()->randomElement(CustomerDocumentCategory::values()),
            'status' => CustomerDocumentStatus::Active->value,
            'disk' => 'public',
            'path' => 'customer-documents/demo/'.$group.'.'.$extension,
            'original_filename' => 'demo.'.$extension,
            'extension' => $extension,
            'mime_type' => match ($extension) {
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            },
            'size' => fake()->numberBetween(10_000, 500_000),
            'expires_at' => fake()->optional()->dateTimeBetween('+1 month', '+2 years'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->id,
        ]);
    }
}
