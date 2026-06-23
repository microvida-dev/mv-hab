<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Pedidos dos titulares</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.privacy.requests.store') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-2">
            @csrf
            <select name="request_type" class="mv-input" required>@foreach (\App\Enums\DataSubjectRequestType::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            <select name="user_id" class="mv-input"><option value="">Titular não associado</option>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>@endforeach</select>
            <textarea name="description" class="mv-input md:col-span-2" rows="3" placeholder="Descrição do pedido" required></textarea>
            <button class="mv-button-primary w-fit">Registar pedido</button>
        </form>
        <section class="mv-surface overflow-hidden">
            <table class="mv-table"><thead><tr><th>Número</th><th>Tipo</th><th>Estado</th><th>Titular</th><th>Prazo</th><th></th></tr></thead><tbody>@foreach ($requests as $requestRecord)<tr><td>{{ $requestRecord->request_number }}</td><td>{{ $requestRecord->request_type?->label() }}</td><td>{{ $requestRecord->status?->label() }}</td><td>{{ $requestRecord->user?->name ?? $requestRecord->requester_email }}</td><td>{{ $requestRecord->due_at?->format('d/m/Y') }}</td><td><a class="mv-link" href="{{ route('backoffice.security.privacy.requests.show', $requestRecord) }}">Ver</a></td></tr>@endforeach</tbody></table>
            <div class="p-4">{{ $requests->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
