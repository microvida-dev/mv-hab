<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Área pessoal</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Processos administrativos</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <div class="grid gap-4">
                @forelse ($processes as $process)
                    @php($snapshot = $process->application?->publicStatusSnapshot)
                    <section class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase text-civic-700">{{ $process->process_number }}</p>
                                <h2 class="mt-1 text-lg font-semibold text-ink-900">{{ $snapshot?->title ?? $process->status->label() }}</h2>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">{{ $snapshot?->description ?? 'Processo associado à candidatura.' }}</p>
                                @if ($snapshot?->next_step)
                                    <p class="mt-2 text-sm font-semibold text-ink-800">Próximo passo: {{ $snapshot->next_step }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-ink-900">{{ $process->application?->application_number ?? 'Sem número' }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $snapshot?->progress_percentage ?? 0 }}% concluído</p>
                                <a href="{{ route('candidate.processes.show', $process) }}" class="mt-3 inline-flex rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Consultar</a>
                            </div>
                        </div>
                    </section>
                @empty
                    <section class="mv-surface p-8 text-center text-sm text-ink-500">Ainda não existem processos administrativos associados às suas candidaturas.</section>
                @endforelse
            </div>
            {{ $processes->links() }}
        </div>
    </div>
</x-app-layout>
