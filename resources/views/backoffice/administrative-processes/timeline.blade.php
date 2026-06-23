<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Timeline processual</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $process->process_number }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
            @foreach ($timeline as $event)
                <section class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">{{ $event['date']?->format('d/m/Y H:i') }} · {{ $event['type'] }}</p>
                    <h2 class="mt-1 text-base font-semibold text-ink-900">{{ $event['title'] }}</h2>
                    @if ($event['description'])
                        <p class="mt-2 text-sm leading-6 text-ink-600">{{ $event['description'] }}</p>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
