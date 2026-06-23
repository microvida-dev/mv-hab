<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $event->event_number }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6">
            <dl class="grid gap-3 text-sm">
                <div><dt class="font-semibold text-ink-700">Evento</dt><dd>{{ $event->event_code }}</dd></div>
                <div><dt class="font-semibold text-ink-700">Ator</dt><dd>{{ $event->user?->name ?? 'Sistema' }}</dd></div>
                <div><dt class="font-semibold text-ink-700">Titular</dt><dd>{{ $event->subjectUser?->name ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-ink-700">Descrição</dt><dd>{{ $event->description }}</dd></div>
            </dl>
        </section>
        <section class="grid gap-6 md:grid-cols-2">
            <pre class="mv-surface overflow-auto p-4 text-xs">{{ json_encode($event->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            <pre class="mv-surface overflow-auto p-4 text-xs">{{ json_encode($event->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    </div></div>
</x-app-layout>
