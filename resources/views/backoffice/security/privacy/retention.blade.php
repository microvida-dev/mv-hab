<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Retenção de dados</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.privacy.retention.store') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-2">
            @csrf
            <input name="code" class="mv-input" placeholder="Código" required><input name="name" class="mv-input" placeholder="Nome" required>
            <input name="entity_type" class="mv-input" value="App\Models\DataSubjectRequest" required><input name="retention_period_months" class="mv-input" type="number" min="0" value="60" required>
            <select name="retention_action" class="mv-input" required>@foreach (\App\Enums\RetentionAction::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            <label class="text-sm"><input type="checkbox" name="requires_manual_approval" value="1" checked> Exige aprovação manual</label>
            <textarea name="legal_basis" class="mv-input md:col-span-2" rows="2" placeholder="Base legal"></textarea>
            <button class="mv-button-primary w-fit">Criar política</button>
        </form>
        <section class="mv-surface overflow-hidden">
            <table class="mv-table"><thead><tr><th>Política</th><th>Entidade</th><th>Ação</th><th>Meses</th><th></th></tr></thead><tbody>@foreach ($policies as $policy)<tr><td>{{ $policy->name }}</td><td>{{ $policy->entity_type }}</td><td>{{ $policy->retention_action?->label() }}</td><td>{{ $policy->retention_period_months }}</td><td><form method="POST" action="{{ route('backoffice.security.privacy.retention.simulate', $policy) }}">@csrf<button class="mv-link">Simular</button></form></td></tr>@endforeach</tbody></table>
            <div class="p-4">{{ $policies->links() }}</div>
        </section>
        <section class="mv-surface overflow-hidden">
            <div class="p-5 font-semibold">Execuções recentes</div>
            <table class="mv-table"><tbody>@foreach ($executions as $execution)<tr><td>{{ $execution->execution_number }}</td><td>{{ $execution->policy?->name }}</td><td>{{ $execution->status?->label() }}</td><td>{{ $execution->matched_records_count }}</td><td class="flex gap-2"><form method="POST" action="{{ route('backoffice.security.privacy.retention-executions.approve', $execution) }}">@csrf<button class="mv-link">Aprovar</button></form><form method="POST" action="{{ route('backoffice.security.privacy.retention-executions.run', $execution) }}">@csrf<button class="mv-link">Executar</button></form></td></tr>@endforeach</tbody></table>
        </section>
    </div></div>
</x-app-layout>
