<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Relatórios municipais"
            title="Exportações temporais de candidaturas"
            description="Pacotes privados, versionados e reproduzíveis por concurso."
        >
            <x-slot name="actions">
                @can('createTemporal', \App\Models\ReportExport::class)
                    <a class="mv-button-primary" href="{{ route('backoffice.reports.temporal-exports.create') }}">
                        Nova exportação
                    </a>
                @endcan
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-flash-message />

        <x-mv.alert>
            Os artefactos são guardados em storage privado durante sete dias. A descarga volta a validar permissões, Município, estado e expiração.
        </x-mv.alert>

        <x-ui.table :headers="['Exportação', 'Concurso', 'Modo', 'Formatos e tamanho', 'Proteção', 'Estado', 'Expira', 'Ações']">
            @forelse ($exports as $export)
                @php
                    $tone = match ($export->status) {
                        \App\Enums\ReportExportStatus::Completed => 'success',
                        \App\Enums\ReportExportStatus::Failed,
                        \App\Enums\ReportExportStatus::Expired,
                        \App\Enums\ReportExportStatus::Cancelled => 'danger',
                        \App\Enums\ReportExportStatus::Processing => 'warning',
                        default => 'neutral',
                    };
                @endphp
                <tr>
                    <td>
                        <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.reports.temporal-exports.show', $export) }}">
                            {{ $export->public_id }}
                        </a>
                        <p class="mt-1 text-xs text-ink-500">Pedido em {{ $export->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mt-1 text-xs text-ink-500">Por {{ $export->user?->name ?? 'Utilizador indisponível' }}</p>
                    </td>
                    <td>
                        <span class="font-medium text-ink-900">{{ $export->contest?->code }}</span>
                        <p class="mt-1 text-xs text-ink-500">{{ $export->contest?->title }}</p>
                    </td>
                    <td>{{ $export->export_mode?->label() ?? 'Não aplicável' }}</td>
                    <td>
                        {{ collect($export->formats ?? [])->map(fn ($format) => strtoupper((string) $format))->join(', ') }}
                        <p class="mt-1 text-xs text-ink-500">
                            {{ $export->file_size !== null ? number_format($export->file_size / 1024, 1, ',', '.').' KB' : 'Tamanho pendente' }}
                        </p>
                    </td>
                    <td>
                        <x-mv.badge :tone="$export->sensitive_fields_included ? 'warning' : 'neutral'">
                            {{ $export->sensitive_fields_included ? 'Sensível' : 'Pseudonimizada' }}
                        </x-mv.badge>
                        <p class="mt-1 text-xs text-ink-500">
                            {{ $export->document_files_included ? 'Com ficheiros documentais' : ($export->document_files_requested ? 'Ficheiros excluídos' : 'Sem ficheiros documentais') }}
                        </p>
                    </td>
                    <td>
                        <x-mv.badge :tone="$tone">{{ $export->status->label() }}</x-mv.badge>
                        @if ($export->processing_stage)
                            <p class="mt-1 text-xs text-ink-500">{{ $export->processing_stage->label() }} · {{ $export->progress }}%</p>
                        @endif
                    </td>
                    <td>{{ $export->expires_at?->format('d/m/Y H:i') ?? 'Sem data' }}</td>
                    <td>
                        <a class="mv-button-secondary inline-flex" href="{{ route('backoffice.reports.temporal-exports.show', $export) }}">
                            Consultar
                        </a>
                    </td>
                </tr>
            @empty
                <x-ui.table-empty :colspan="8" message="Ainda não existem exportações temporais." />
            @endforelse
        </x-ui.table>

        <div>{{ $exports->links() }}</div>
    </div>
</x-app-layout>
