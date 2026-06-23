<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><div><h1 class="text-xl font-semibold text-ink-900">{{ $report->name }}</h1><p class="mt-1 text-sm text-ink-500">{{ $report->description }}</p></div>@can('update', $report)<a class="mv-button-secondary" href="{{ route('backoffice.reports.definitions.edit', $report) }}">Editar</a>@endcan</div></x-slot>
    <div class="space-y-7 max-w-5xl" x-data="{ confirmed: false }">
        @if($report->sensitivity_level->requiresConfirmation())<div class="border-l-4 border-warning-500 bg-warning-50 p-4 text-sm text-ink-800">Este relatório pode conter dados pessoais ou informação sensível. A consulta e exportação ficam registadas para efeitos de auditoria.</div>@endif
        <form method="POST" action="{{ route('backoffice.reports.runs.store', $report) }}" class="space-y-4">@csrf
            <h2 class="text-lg font-semibold text-ink-900">Executar relatório</h2>
            <div class="grid gap-3 md:grid-cols-4"><x-text-input name="date_from" type="date" /><x-text-input name="date_to" type="date" /><x-text-input name="program_id" type="number" placeholder="ID do programa" /><x-text-input name="contest_id" type="number" placeholder="ID do concurso" /></div>
            <input type="hidden" name="format" value="html"><input type="hidden" name="scope" value="aggregated"><button class="mv-button-primary">Consultar</button>
        </form>
        @can('export', $report)
            <form method="POST" action="{{ route('backoffice.reports.exports.store', $report) }}" class="space-y-4 border-t border-ink-100 pt-6">@csrf
                <h2 class="text-lg font-semibold text-ink-900">Exportar</h2>
                <div class="grid gap-3 md:grid-cols-4">
                    <select name="format" class="rounded-md border-ink-200">@foreach($report->available_formats as $format)<option value="{{ $format }}">{{ strtoupper($format) }}</option>@endforeach</select>
                    <select name="scope" class="rounded-md border-ink-200">@foreach($report->available_scopes as $scope)<option value="{{ $scope }}">{{ \App\Enums\ExportScope::from($scope)->label() }}</option>@endforeach</select>
                    <x-text-input name="date_from" type="date" /><x-text-input name="date_to" type="date" />
                </div>
                @if($report->sensitivity_level->requiresConfirmation())<label class="flex items-start gap-3 text-sm text-ink-700"><input type="checkbox" name="confirmed" value="1" required>Confirma a exportação deste relatório? A operação ficará registada com os filtros aplicados, data, utilizador e formato exportado.</label>@endif
                <button class="mv-button-primary">Criar exportação</button>
            </form>
        @endcan
    </div>
</x-app-layout>
