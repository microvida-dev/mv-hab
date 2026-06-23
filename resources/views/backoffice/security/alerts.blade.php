<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Alertas de segurança</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.alert-rules.store') }}" class="mv-surface grid gap-4 p-5 md:grid-cols-3">
            @csrf
            <input name="code" class="mv-input" placeholder="Código" required><input name="name" class="mv-input" placeholder="Nome" required><input name="event_code" class="mv-input" placeholder="Evento" required>
            <select name="severity" class="mv-input">@foreach (\App\Enums\SecurityAlertSeverity::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            <input name="threshold" type="number" min="1" value="5" class="mv-input"><input name="window_minutes" type="number" min="1" value="15" class="mv-input">
            <label class="text-sm"><input type="checkbox" name="is_active" value="1" checked> Ativa</label><textarea name="description" class="mv-input md:col-span-3" rows="2" placeholder="Descrição"></textarea>
            <button class="mv-button-primary w-fit">Criar regra</button>
        </form>
        <section class="mv-surface overflow-hidden">
            <div class="p-5 font-semibold">Alertas</div>
            <table class="mv-table"><thead><tr><th>Data</th><th>Severidade</th><th>Estado</th><th>Título</th><th>Ações</th></tr></thead><tbody>@foreach ($alerts as $alert)<tr><td>{{ $alert->detected_at?->format('d/m/Y H:i') }}</td><td>{{ $alert->severity?->label() }}</td><td>{{ $alert->status?->label() }}</td><td>{{ $alert->title }}</td><td class="flex gap-2"><form method="POST" action="{{ route('backoffice.security.alerts.review', $alert) }}">@csrf<button class="mv-link">Analisar</button></form><form method="POST" action="{{ route('backoffice.security.alerts.resolve', $alert) }}">@csrf<input type="hidden" name="resolution_notes" value="Resolvido em revisão municipal."><button class="mv-link">Resolver</button></form></td></tr>@endforeach</tbody></table>
            <div class="p-4">{{ $alerts->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
