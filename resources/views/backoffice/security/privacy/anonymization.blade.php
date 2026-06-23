<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Anonimização</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.privacy.anonymization.store') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-2">
            @csrf
            <select name="user_id" class="mv-input" required>@foreach ($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>@endforeach</select>
            <input name="anonymization_type" class="mv-input" value="user_profile" required>
            <label class="text-sm"><input type="checkbox" name="scope[]" value="user.profile" checked> Perfil de utilizador</label>
            <textarea name="reason" class="mv-input md:col-span-2" rows="3" placeholder="Fundamento do pedido" required></textarea>
            <button class="mv-button-primary w-fit">Criar pedido</button>
        </form>
        <section class="mv-surface overflow-hidden">
            <table class="mv-table"><thead><tr><th>Número</th><th>Estado</th><th>Titular</th><th>Tipo</th><th></th></tr></thead><tbody>@foreach ($requests as $anon)<tr><td>{{ $anon->request_number }}</td><td>{{ $anon->status?->label() }}</td><td>{{ $anon->user?->name ?? '—' }}</td><td>{{ $anon->anonymization_type }}</td><td><a class="mv-link" href="{{ route('backoffice.security.privacy.anonymization.show', $anon) }}">Ver</a></td></tr>@endforeach</tbody></table>
            <div class="p-4">{{ $requests->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
