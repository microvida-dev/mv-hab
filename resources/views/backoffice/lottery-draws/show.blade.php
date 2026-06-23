<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Sorteios</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Sorteio #{{ $lotteryDraw->id }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="rounded-md border border-ink-100 bg-white p-6">
            <dl class="grid gap-4 text-sm md:grid-cols-4">
                <div><dt class="text-ink-500">Concurso</dt><dd class="font-semibold">{{ $lotteryDraw->contest?->title }}</dd></div>
                <div><dt class="text-ink-500">Estado</dt><dd>{{ $lotteryDraw->status->label() }}</dd></div>
                <div><dt class="text-ink-500">Participantes</dt><dd>{{ $lotteryDraw->participants_count }}</dd></div>
                <div><dt class="text-ink-500">Resultado</dt><dd class="break-all font-mono text-xs">{{ $lotteryDraw->result_hash ?? 'Por gerar' }}</dd></div>
            </dl>
            <p class="mt-4 text-sm text-ink-600">O sorteio deve ser validado pelos serviços competentes antes de produzir efeitos administrativos definitivos. O resultado registado na plataforma é auditável e fica associado ao procedimento.</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('backoffice.lottery-draws.participants.load', $lotteryDraw) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Carregar participantes</button></form>
                <form method="POST" action="{{ route('backoffice.lottery-draws.participants.lock', $lotteryDraw) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Bloquear participantes</button></form>
                <form method="POST" action="{{ route('backoffice.lottery-draws.run', $lotteryDraw) }}">@csrf<button class="rounded-md bg-civic-700 px-3 py-2 text-sm font-semibold text-white">Executar</button></form>
                <form method="POST" action="{{ route('backoffice.lottery-draws.validate', $lotteryDraw) }}">@csrf<button class="rounded-md border border-civic-300 px-3 py-2 text-sm font-semibold text-civic-800">Validar resultado</button></form>
                <a href="{{ route('backoffice.lottery-draws.results.index', $lotteryDraw) }}" class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Resultados</a>
                <a href="{{ route('backoffice.lottery-draws.attendance.index', $lotteryDraw) }}" class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Presenças</a>
            </div>
        </div>
        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-md border border-ink-100 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Participantes</h2>
                <div class="mt-3 divide-y divide-ink-100 text-sm">
                    @forelse($lotteryDraw->participants as $participant)
                        <div class="py-2">{{ $participant->participant_number }} — {{ $participant->candidate?->name }} <span class="text-ink-500">({{ $participant->status->label() }})</span></div>
                    @empty
                        <p class="py-3 text-ink-500">Sem participantes carregados.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-md border border-ink-100 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Ações pós-sorteio</h2>
                <div class="mt-3 space-y-2">
                    <form method="POST" action="{{ route('backoffice.lottery-draws.convocations.generate', $lotteryDraw) }}" class="grid gap-2 md:grid-cols-3">@csrf<input name="scheduled_for" type="datetime-local" class="rounded-md border-ink-200"><input name="location" placeholder="Local" class="rounded-md border-ink-200"><button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Gerar convocatórias</button></form>
                    <form method="POST" action="{{ route('backoffice.lottery-draws.ranking.update', $lotteryDraw) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Atualizar ranking</button></form>
                    <form method="POST" action="{{ route('backoffice.lottery-draws.post-draw-report.generate', $lotteryDraw) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold">Gerar relatório</button></form>
                </div>
            </div>
        </div>
    </div></div>
</x-app-layout>
