<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Revisões de renda</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.finance.rent-reviews.create') }}">Criar revisão</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($reviews as $review)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.rent-reviews.show', $review) }}">Revisão #{{ $review->id }}</a></td><td>{{ $review->tenant?->name }}</td><td>{{ $review->status->label() }}</td></tr>@endforeach</tbody></table>{{ $reviews->links() }}</div>
</x-app-layout>
