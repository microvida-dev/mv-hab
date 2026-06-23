<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Estado público</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6">
            <p class="text-sm font-semibold text-civic-700">{{ $application->application_number ?? $application->public_id }}</p>
            <h2 class="mt-2 text-xl font-semibold text-ink-900">{{ $snapshot->title }}</h2>
            <p class="mt-3 text-sm text-ink-600">{{ $snapshot->description }}</p>
            @if ($snapshot->next_step)<p class="mt-3 text-sm font-semibold text-ink-800">Próximo passo: {{ $snapshot->next_step }}</p>@endif
            <form method="POST" action="{{ route('backoffice.applications.public-status.update', $application) }}" class="mt-5">
                @csrf
                @method('PUT')
                <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Recalcular</button>
            </form>
        </section>
    </div></div>
</x-app-layout>
