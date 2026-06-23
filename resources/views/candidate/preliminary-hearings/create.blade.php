<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Submeter audiência prévia</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6">
            <p class="text-sm text-ink-600">{{ $hearing->subject }} · prazo {{ $hearing->deadline_at->format('d/m/Y H:i') }}</p>
            <form method="POST" action="{{ route('candidate.hearings.submit.store', $hearing) }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="application_id" value="{{ $hearing->application_id }}">
                <input type="hidden" name="subject" value="{{ $hearing->subject }}">
                <textarea name="body" rows="7" required class="block w-full rounded-md border-ink-200"></textarea>
                <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Submeter pronúncia</button>
            </form>
        </section>
    </div></div>
</x-app-layout>
