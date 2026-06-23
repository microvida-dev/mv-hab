<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Finalidades RGPD</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.privacy.purposes.store') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-2">
            @csrf
            <input name="code" class="mv-input" placeholder="Código" required>
            <input name="name" class="mv-input" placeholder="Nome" required>
            <select name="legal_basis" class="mv-input" required>@foreach (\App\Enums\ConsentLegalBasis::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            <input name="retention_period_months" class="mv-input" type="number" min="0" placeholder="Retenção em meses">
            <textarea name="description" class="mv-input md:col-span-2" rows="3" placeholder="Descrição da finalidade" required></textarea>
            <label class="text-sm"><input type="checkbox" name="is_required" value="1"> Obrigatória</label>
            <label class="text-sm"><input type="checkbox" name="requires_explicit_consent" value="1"> Exige consentimento explícito</label>
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" checked> Ativa</label>
            <button class="mv-button-primary w-fit">Criar finalidade</button>
        </form>
        <section class="mv-surface overflow-hidden">
            <table class="mv-table"><thead><tr><th>Código</th><th>Nome</th><th>Base legal</th><th>Ativa</th><th>Retenção</th></tr></thead><tbody>@foreach ($purposes as $purpose)<tr><td>{{ $purpose->code }}</td><td>{{ $purpose->name }}</td><td>{{ $purpose->legal_basis?->label() }}</td><td>{{ $purpose->is_active ? 'sim' : 'não' }}</td><td>{{ $purpose->retention_period_months ?? '—' }}</td></tr>@endforeach</tbody></table>
            <div class="p-4">{{ $purposes->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
