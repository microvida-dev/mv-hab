<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Revisões de renda</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($reviews as $review)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.rent-reviews.show', $review) }}">Revisão #{{ $review->id }}</a></td><td>{{ $review->status->label() }}</td><td>{{ $review->effective_from?->format('d/m/Y') }}</td></tr>@endforeach</tbody></table>{{ $reviews->links() }}</div>
</x-app-layout>
