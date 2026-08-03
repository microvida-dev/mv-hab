@php
    $pageTitle = ($export->contest?->code ?? 'Concurso').' · '.($export->export_mode?->label() ?? 'Exportação temporal');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Exportação temporal"
            :title="$pageTitle"
            description="Pacote municipal privado com origem temporal verificável."
        >
            <x-slot name="actions">
                <a class="mv-button-secondary" href="{{ route('backoffice.reports.temporal-exports.index') }}">Histórico</a>
                @if ($export->status === \App\Enums\ReportExportStatus::Completed)
                    @can('download', $export)
                        <a class="mv-button-primary" href="{{ route('backoffice.reports.temporal-exports.download', $export) }}">Descarregar ZIP</a>
                    @endcan
                @endif
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    @php
        $tone = match ($export->status) {
            \App\Enums\ReportExportStatus::Completed => 'success',
            \App\Enums\ReportExportStatus::Failed,
            \App\Enums\ReportExportStatus::Expired,
            \App\Enums\ReportExportStatus::Cancelled => 'danger',
            \App\Enums\ReportExportStatus::Processing => 'warning',
            default => 'neutral',
        };
        $metadata = is_array($export->source_metadata) ? $export->source_metadata : [];
        $counts = is_array($metadata['counts'] ?? null) ? $metadata['counts'] : [];
        $warnings = is_array($metadata['warnings'] ?? null) ? $metadata['warnings'] : [];
    @endphp

    <div class="space-y-6">
        <x-flash-message />

        <x-mv.alert>
            O pacote e o respetivo manifesto são documentos internos. O caminho de storage permanece oculto e cada descarga é auditada.
        </x-mv.alert>

        <x-mv.section title="Estado da geração">
            <div aria-live="polite" class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <x-mv.badge :tone="$tone">{{ $export->status->label() }}</x-mv.badge>
                    @if ($export->processing_stage)
                        <span class="text-sm font-medium text-ink-700">{{ $export->processing_stage->label() }}</span>
                    @endif
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-ink-500">
                        <span>Progresso</span>
                        <span>{{ $export->progress }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-2xl bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $export->progress }}">
                        <div class="h-full bg-mvhab-primary" style="width: {{ max(0, min(100, $export->progress)) }}%"></div>
                    </div>
                </div>
                @if (in_array($export->status, [\App\Enums\ReportExportStatus::Pending, \App\Enums\ReportExportStatus::Processing], true))
                    <p class="text-sm text-ink-500">A página pode ser atualizada para consultar o progresso mais recente.</p>
                    <a class="mv-button-secondary inline-flex" href="{{ route('backoffice.reports.temporal-exports.show', $export) }}">Atualizar estado</a>
                @endif
                @if ($export->status === \App\Enums\ReportExportStatus::Failed)
                    <x-mv.alert tone="danger">
                        {{ $export->error_message ?: 'A exportação falhou de forma controlada.' }}
                        @if ($export->failure_code)
                            <span class="block text-xs">Código: {{ $export->failure_code }}</span>
                        @endif
                    </x-mv.alert>
                @endif
            </div>
        </x-mv.section>

        <x-mv.section title="Origem e configuração">
            <dl class="grid gap-5 text-sm md:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-ink-500">ID público</dt><dd class="mt-1 break-all font-medium text-ink-900">{{ $export->public_id }}</dd></div>
                <div><dt class="text-ink-500">Município</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->municipality?->name }}</dd></div>
                <div><dt class="text-ink-500">Concurso</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->contest?->code }} · {{ $export->contest?->title }}</dd></div>
                <div><dt class="text-ink-500">Modo</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->export_mode?->label() }}</dd></div>
                <div><dt class="text-ink-500">Snapshot</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->snapshot_at?->format('d/m/Y H:i:s') ?? 'A aguardar captura' }}</dd></div>
                <div><dt class="text-ink-500">Formatos</dt><dd class="mt-1 font-medium text-ink-900">{{ collect($export->formats ?? [])->map(fn ($format) => strtoupper((string) $format))->join(', ') }}</dd></div>
                <div><dt class="text-ink-500">Datasets</dt><dd class="mt-1 font-medium text-ink-900">{{ collect($export->datasets ?? [])->join(', ') }}</dd></div>
                <div><dt class="text-ink-500">Pedido por</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->user?->name }}</dd></div>
                <div><dt class="text-ink-500">Criada em</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->created_at?->format('d/m/Y H:i:s') }}</dd></div>
                <div><dt class="text-ink-500">Concluída em</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->completed_at?->format('d/m/Y H:i:s') ?? 'Não concluída' }}</dd></div>
                <div><dt class="text-ink-500">Expira em</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->expires_at?->format('d/m/Y H:i:s') }}</dd></div>
                <div><dt class="text-ink-500">Tamanho</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->file_size !== null ? number_format($export->file_size / 1024, 1, ',', '.').' KB' : 'A aguardar' }}</dd></div>
            </dl>
        </x-mv.section>

        <x-mv.section title="Conteúdo e integridade">
            <dl class="grid gap-5 text-sm md:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-ink-500">Candidaturas</dt><dd class="mt-1 text-lg font-semibold text-ink-900">{{ $counts['applications'] ?? 0 }}</dd></div>
                <div><dt class="text-ink-500">Documentos</dt><dd class="mt-1 text-lg font-semibold text-ink-900">{{ $counts['documents'] ?? 0 }}</dd></div>
                <div><dt class="text-ink-500">Achados</dt><dd class="mt-1 text-lg font-semibold text-ink-900">{{ $counts['findings'] ?? 0 }}</dd></div>
                <div><dt class="text-ink-500">Alterações</dt><dd class="mt-1 text-lg font-semibold text-ink-900">{{ $counts['changes'] ?? 0 }}</dd></div>
                <div class="md:col-span-2"><dt class="text-ink-500">Source fingerprint</dt><dd class="mt-1 break-all font-mono text-xs text-ink-900">{{ $export->source_fingerprint ?? 'A aguardar' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-ink-500">SHA-256 do manifesto</dt><dd class="mt-1 break-all font-mono text-xs text-ink-900">{{ $export->manifest_sha256 ?? 'A aguardar' }}</dd></div>
                <div class="md:col-span-2"><dt class="text-ink-500">SHA-256 do pacote</dt><dd class="mt-1 break-all font-mono text-xs text-ink-900">{{ $export->package_sha256 ?? 'A aguardar' }}</dd></div>
                <div><dt class="text-ink-500">Campos sensíveis</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->sensitive_fields_included ? 'Incluídos com autorização' : 'Não incluídos' }}</dd></div>
                <div><dt class="text-ink-500">Ficheiros documentais</dt><dd class="mt-1 font-medium text-ink-900">{{ $export->document_files_included ? 'Incluídos' : ($export->document_files_requested ? 'Pedidos, mas excluídos por segurança' : 'Não pedidos') }}</dd></div>
            </dl>

            @if ($warnings !== [])
                <x-mv.alert class="mt-5" tone="warning">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </x-mv.alert>
            @endif
        </x-mv.section>
    </div>
</x-app-layout>
