<?php

namespace App\Services\Search\Sources;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'support_ticket';
    }

    public function label(): string
    {
        return 'Tickets';
    }

    public function minimumCharacters(): int
    {
        return 2;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(User $user, string $term, int $limit): array
    {
        if (! $this->authorization->canAccess($user, 'backoffice.support-tickets.show', 'support.view')) {
            return [];
        }

        return array_values(SupportTicket::query()
            ->select(['id', 'ticket_number', 'category', 'priority', 'status', 'assigned_to', 'last_message_at', 'created_at'])
            ->visibleToBackofficeUser($user)
            ->where(function (Builder $query) use ($term): void {
                $query->where('ticket_number', 'like', '%'.$term.'%')
                    ->orWhere('subject', 'like', '%'.$term.'%');
            })
            ->latest('last_message_at')
            ->limit($limit)
            ->get()
            ->map(fn (SupportTicket $ticket): array => [
                'type' => 'support_ticket',
                'group_key' => 'support_tickets',
                'group_label' => $this->label(),
                'label' => 'Ticket '.$ticket->ticket_number,
                'subtitle' => 'Categoria: '.$this->enumLabel($ticket->category).' · Estado: '.$this->enumLabel($ticket->status),
                'route_name' => 'backoffice.support-tickets.show',
                'route_parameters' => [$ticket->getRouteKey()],
                'score' => 74,
            ])
            ->all());
    }
}
