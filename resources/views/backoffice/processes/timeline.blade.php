<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Timeline completa da candidatura</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
        @foreach ($phases as $phase => $events)
            <section class="space-y-3">
                <h2 class="text-base font-semibold text-ink-900">{{ $phase }}</h2>
                @foreach ($events as $event)
                    <article class="mv-surface p-5">
                        <p class="text-xs font-semibold uppercase text-ink-500">{{ $event['date']?->format('d/m/Y H:i') }} · {{ $event['visibility'] }}</p>
                        <h3 class="mt-1 font-semibold text-ink-900">{{ $event['title'] }}</h3>
                        @if ($event['description'])<p class="mt-2 text-sm text-ink-600">{{ $event['description'] }}</p>@endif
                    </article>
                @endforeach
            </section>
        @endforeach
    </div></div>
</x-app-layout>
