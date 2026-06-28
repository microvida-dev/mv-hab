<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Cronologia</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $process->process_number }}</h1>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-5">
                <p class="text-sm leading-6 text-ink-600">Esta timeline apresenta o histórico do seu processo com base nos atos registados na plataforma. Algumas etapas podem depender de validação documental, análise técnica ou decisão dos serviços municipais.</p>
            </section>
            @forelse ($phases as $phase => $events)
                <section class="space-y-3">
                    <h2 class="text-base font-semibold text-ink-900">{{ $phase }}</h2>
                    @foreach ($events as $event)
                        <article class="mv-surface p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-ink-500">{{ $event['date']?->format('d/m/Y H:i') }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-ink-900">{{ $event['title'] }}</h3>
                                </div>
                                <span class="rounded-md bg-ink-50 px-2.5 py-1 text-xs font-semibold text-ink-600">{{ str($event['type'])->replace('_', ' ')->headline() }}</span>
                            </div>
                            @if ($event['description'])
                                <p class="mt-2 text-sm leading-6 text-ink-600">{{ $event['description'] }}</p>
                            @endif
                            @if ($event['due_at'])
                                <p class="mt-2 text-xs font-semibold text-civic-700">Prazo associado: {{ $event['due_at']->format('d/m/Y H:i') }}</p>
                            @endif
                        </article>
                    @endforeach
                </section>
            @empty
                <section class="mv-surface p-8 text-center text-sm text-ink-500">Ainda não existem eventos registados.</section>
            @endforelse
        </div>
    </div>
</x-app-layout>
