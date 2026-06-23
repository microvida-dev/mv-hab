<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Relatório de candidatura</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $application->application_number ?? 'Candidatura' }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $application->contest->title ?? 'Sem concurso associado' }}</p>
            </div>
            <a href="{{ route('backoffice.applications.show', $application) }}" class="mv-button-secondary">Voltar à candidatura</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>
            @endif

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Gerar relatório operacional</h2>
                <p class="mt-2 text-sm leading-6 text-ink-600">Este documento foi gerado automaticamente com base nos dados registados na plataforma à data da emissão. A validação final compete aos serviços municipais.</p>
                <form method="POST" action="{{ route('backoffice.applications.report.generate', $application) }}" class="mt-5 grid gap-4 md:grid-cols-4">
                    @csrf
                    <label class="text-sm font-semibold text-ink-700">Formato
                        <select name="format" class="mt-1 w-full rounded-md border-ink-200">
                            @foreach (\App\Enums\ReportFormat::cases() as $format)
                                <option value="{{ $format->value }}">{{ $format->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="include_documents" value="1" class="rounded border-ink-300"> Documentos</label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="include_timeline" value="1" class="rounded border-ink-300"> Timeline</label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="include_internal_notes" value="1" class="rounded border-ink-300"> Notas internas</label>
                    <div class="md:col-span-4">
                        <button class="mv-button-primary">Gerar relatório</button>
                    </div>
                </form>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Relatórios gerados</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="mv-table">
                        <thead><tr><th>Número</th><th>Título</th><th>Estado</th><th>Formato</th><th>Gerado em</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="font-mono text-xs">{{ $report->report_number }}</td>
                                    <td>{{ $report->title }}</td>
                                    <td>{{ $report->status->label() }}</td>
                                    <td>{{ $report->format->label() }}</td>
                                    <td>{{ $report->generated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-right">
                                        @if ($report->file_path)
                                            <a class="font-semibold text-civic-700" href="{{ route('backoffice.application-reports.download', $report) }}">Download</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">Sem relatórios gerados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $reports->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
