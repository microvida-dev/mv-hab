<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Cronologia processual"
            :title="$process->process_number"
            description="Histórico cronológico dos eventos administrativos associados ao processo."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
            @foreach ($timeline as $event)
                <x-mv.section>
                    <p class="text-xs font-semibold uppercase text-ink-500">{{ $event['date']?->format('d/m/Y H:i') }} · {{ $event['type'] }}</p>
                    <h2 class="mt-1 text-base font-semibold text-ink-900">{{ $event['title'] }}</h2>
                    @if ($event['description'])
                        <p class="mt-2 text-sm leading-6 text-ink-600">{{ $event['description'] }}</p>
                    @endif
                </x-mv.section>
            @endforeach
        </div>
    </div>
</x-app-layout>
