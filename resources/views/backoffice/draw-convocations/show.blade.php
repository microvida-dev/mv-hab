<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Convocatórias</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Convocatória #{{ $drawConvocation->id }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8"><x-flash-message /><div class="rounded-2xl border border-ink-100 bg-mvhab-card p-6 text-sm">
        <dl class="grid gap-4 md:grid-cols-2"><div><dt class="text-ink-500">Candidato</dt><dd class="font-semibold">{{ $drawConvocation->candidate?->name }}</dd></div><div><dt class="text-ink-500">Estado</dt><dd>{{ $drawConvocation->status->label() }}</dd></div><div><dt class="text-ink-500">Data</dt><dd>{{ $drawConvocation->scheduled_for?->format('d/m/Y H:i') }}</dd></div><div><dt class="text-ink-500">Local</dt><dd>{{ $drawConvocation->location }}</dd></div></dl>
        <p class="mt-4 text-ink-600">{{ $drawConvocation->instructions }}</p>
        <form method="POST" action="{{ route('backoffice.draw-convocations.send', $drawConvocation) }}" class="mt-5">@csrf<button class="mv-button-primary">Marcar como enviada</button></form>
    </div></div></div>
</x-app-layout>
