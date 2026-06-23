@if(!($printMode ?? false))
<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Relatórios</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $report?->title ?? 'Relatório pós-sorteio' }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
@endif
        <section class="rounded-md border border-ink-100 bg-white p-6 text-sm">
            <h2 class="text-xl font-semibold text-ink-900">Relatório pós-sorteio</h2>
            <p class="mt-2 text-ink-600">Dados do concurso, método, participantes, presenças, resultado, vencedor e hashes auditáveis.</p>
            <dl class="mt-5 grid gap-4 md:grid-cols-3">
                <div><dt class="text-ink-500">Concurso</dt><dd class="font-semibold">{{ $lotteryDraw?->contest?->title }}</dd></div>
                <div><dt class="text-ink-500">Método</dt><dd>{{ $lotteryDraw?->algorithm }}</dd></div>
                <div><dt class="text-ink-500">Hash resultado</dt><dd class="break-all font-mono text-xs">{{ $lotteryDraw?->result_hash }}</dd></div>
            </dl>
            <h3 class="mt-6 font-semibold">Resultados</h3>
            <div class="mt-2 divide-y divide-ink-100">
                @foreach(($lotteryDraw?->results ?? collect()) as $result)
                    <div class="py-2">{{ $result->draw_order }} — {{ $result->candidate?->name }} — {{ $result->result_type->label() }}</div>
                @endforeach
            </div>
            @if($report)
                <a class="mt-5 inline-flex rounded-md border border-ink-200 px-3 py-2 font-semibold" href="{{ route('backoffice.post-draw-reports.download', $report) }}">Download HTML</a>
            @endif
        </section>
@if(!($printMode ?? false))
    </div></div>
</x-app-layout>
@endif
