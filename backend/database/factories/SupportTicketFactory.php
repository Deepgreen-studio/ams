<?php

namespace Database\Factories;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'ticket_number' => sprintf('SUP-%s-%05d', now()->format('Ymd'), fake()->unique()->numberBetween(1, 99999)),
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'priority' => fake()->randomElement(SupportTicketPriority::values()),
            'category' => fake()->randomElement(SupportTicketCategory::values()),
            'status' => SupportTicketStatus::Open->value,
            'source' => fake()->randomElement(SupportTicketSource::values()),
            'assigned_to' => null,
            'created_by' => null,
            'updated_by' => null,
            'closed_at' => null,
        ];
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
        ]);
    }

    public function forApplication(Application $application): static
    {
        return $this->state(fn (): array => [
            'company_id' => $application->company_id,
            'application_id' => $application->id,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => [
            'assigned_to' => $user->id,
            'assignment_type' => 'agent',
            'assigned_at' => now(),
            'status' => SupportTicketStatus::InProgress->value,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportTicketStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportTicketStatus::Open->value,
            'closed_at' => null,
        ]);
    }
}
