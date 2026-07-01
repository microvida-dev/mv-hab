@php
    use App\Enums\DocumentAiValidationGroup;
    use App\Enums\DocumentAiValidationSeverity;
    use App\Enums\DocumentAiValidationStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-ink-900">Validação IA documental</h1>
            <p class="text-sm text-ink-500">Cruzamento entre dados declarados na candidatura e campos extraídos dos documentos.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Execuções</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $totals['runs'] }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Revisão</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-700">{{ $totals['requires_review'] }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Críticas</p>
                    <p class="mt-2 text-2xl font-semibold text-red-700">{{ $totals['critical'] }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Médias</p>
                    <p class="mt-2 text-2xl font-semibold text-orange-700">{{ $totals['medium'] }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('backoffice.document-ai.validations.index') }}" class="mv-surface mb-6 grid gap-4 p-5 md:grid-cols-3 xl:grid-cols-7">
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Estado</span>
                    <select name="status" class="mv-input w-full">
                        <option value="">Todos</option>
                        @foreach (DocumentAiValidationStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Severidade</span>
                    <select name="severity" class="mv-input w-full">
                        <option value="">Todas</option>
                        @foreach (DocumentAiValidationSeverity::cases() as $severity)
                            <option value="{{ $severity->value }}" @selected(($filters['severity'] ?? null) === $severity->value)>{{ $severity->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Grupo</span>
                    <select name="group" class="mv-input w-full">
                        <option value="">Todos</option>
                        @foreach (DocumentAiValidationGroup::cases() as $group)
                            <option value="{{ $group->value }}" @selected(($filters['group'] ?? null) === $group->value)>{{ $group->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Revisão</span>
                    <select name="requires_review" class="mv-input w-full">
                        <option value="">Todas</option>
                        <option value="1" @selected(($filters['requires_review'] ?? null) === '1')>Requer revisão</option>
                        <option value="0" @selected(($filters['requires_review'] ?? null) === '0')>Sem revisão</option>
                    </select>
                </label>

                <label class="space-y-1 text-sm xl:col-span-2">
                    <span class="font-semibold text-ink-700">Candidatura</span>
                    <input type="text" name="application" value="{{ $filters['application'] ?? '' }}" class="mv-input w-full" placeholder="Número, public ID ou nome">
                </label>

                <div class="flex items-end gap-2">
                    <button type="submit" class="mv-button-primary w-full justify-center">Filtrar</button>
                    <a href="{{ route('backoffice.document-ai.validations.index') }}" class="mv-button-secondary justify-center">Limpar</a>
                </div>
            </form>

            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Candidatura</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Checks</th>
                            <th class="px-5 py-3">Alertas</th>
                            <th class="px-5 py-3">Revisão</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($runs as $run)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">{{ $run->application?->application_number ?? 'Candidatura sem número' }}</p>
                                    <p class="text-xs text-ink-500">{{ $run->application?->user?->name ?? 'Candidato não identificado' }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $run->status?->label() ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $run->total_checks }} verificação(ões)</td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-red-700">{{ $run->critical_count }}</span>
                                    <span class="text-ink-400">/</span>
                                    <span class="font-semibold text-orange-700">{{ $run->medium_count }}</span>
                                    <span class="text-ink-400">/</span>
                                    <span class="font-semibold text-amber-700">{{ $run->light_count }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="{{ $run->requires_manual_review ? 'text-amber-700' : 'text-mvhab-primary' }}">
                                        {{ $run->requires_manual_review ? 'Requer revisão' : 'Sem revisão' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($run->application)
                                        <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.document-ai.validations.show', $run->application) }}">Ver validação</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem validações IA para os filtros selecionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <div class="mt-4">
                {{ $runs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
