<?php

namespace App\Domains\Support\Services;

use App\Domains\Support\Enums\SupportTicketCategory;
use App\Domains\Support\Enums\SupportTicketMessageAuthorType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketSource;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketMessage;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class PortalSupportTicketService
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
        private readonly SupportTicketConversationService $conversationService,
        private readonly SupportTicketRepository $ticketRepository,
    ) {}

    public function assertPortalCustomer(User $actor): void
    {
        if (! $actor->isPortalCustomer()) {
            throw new ApiException('Customer portal access requires a linked customer account.', 403);
        }

        $actor->loadMissing('customer.company');

        if (! $actor->customer) {
            throw new ApiException('Linked customer profile was not found.', 403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(User $actor): array
    {
        $this->assertPortalCustomer($actor);
        $customer = $actor->customer;

        return [
            'user' => [
                'uuid' => $actor->uuid,
                'full_name' => $actor->full_name,
                'email' => $actor->email,
            ],
            'customer' => [
                'uuid' => $customer->uuid,
                'display_name' => $customer->display_name,
                'email' => $customer->email,
                'company' => $customer->company ? [
                    'uuid' => $customer->company->uuid,
                    'company_name' => $customer->company->company_name,
                ] : null,
            ],
            'categories' => collect(SupportTicketCategory::cases())->map(fn (SupportTicketCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ])->values()->all(),
            'priorities' => collect(SupportTicketPriority::cases())->map(fn (SupportTicketPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listTickets(User $actor, array $filters = []): LengthAwarePaginator
    {
        $this->assertPortalCustomer($actor);

        $filters['customer_id'] = $actor->customer_id;

        return $this->ticketRepository->paginateFiltered($filters);
    }

    public function findOwnedTicket(string $identifier, User $actor): SupportTicket
    {
        $this->assertPortalCustomer($actor);
        $ticket = $this->ticketRepository->findByIdentifierOrFail($identifier);

        if ((int) $ticket->customer_id !== (int) $actor->customer_id) {
            throw new ApiException('You do not have access to this ticket.', 403);
        }

        return $ticket->load([
            'company:id,uuid,company_name',
            'customer:id,uuid,first_name,last_name,company_name,email',
            'assignee:id,uuid,full_name,email',
            'application:id,uuid,name',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTicket(array $data, User $actor): SupportTicket
    {
        $this->assertPortalCustomer($actor);
        $customer = $actor->customer()->with('company')->firstOrFail();

        if (! $customer->company) {
            throw new ApiException('Customer company is required to submit a ticket.', 422);
        }

        return $this->ticketService->create([
            'company_id' => $customer->company->uuid,
            'customer_id' => $customer->uuid,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'category' => $data['category'],
            'priority' => $data['priority'] ?? SupportTicketPriority::Medium->value,
            'source' => SupportTicketSource::Portal->value,
            'application_id' => $data['application_id'] ?? null,
        ], $actor);
    }

    /**
     * @return array{
     *   messages: Collection<int, SupportTicketMessage>,
     *   unread_count: int,
     *   attachment_count: int
     * }
     */
    public function conversation(string $identifier, User $actor): array
    {
        $ticket = $this->findOwnedTicket($identifier, $actor);

        return $this->conversationService->conversation(
            $ticket->uuid,
            $actor,
            [SupportTicketMessageVisibility::Public->value]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $files
     */
    public function reply(string $identifier, array $data, User $actor, array $files = []): SupportTicketMessage
    {
        $ticket = $this->findOwnedTicket($identifier, $actor);

        return $this->conversationService->createMessage($ticket->uuid, [
            'body' => $data['body'] ?? '',
            'body_format' => $data['body_format'] ?? 'html',
            'visibility' => SupportTicketMessageVisibility::Public->value,
            'author_type' => SupportTicketMessageAuthorType::Customer->value,
        ], $actor, $files);
    }
}
