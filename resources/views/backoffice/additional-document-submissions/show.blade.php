<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $submission->title }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface p-6">
            <p class="text-sm text-ink-600">{{ $submission->description }}</p>
            <form method="POST" action="{{ route('backoffice.additional-document-submissions.decide', $submission) }}" class="mt-6 space-y-4">
                @csrf
                <select name="accepted" class="block w-full rounded-md border-ink-200"><option value="1">Aceitar</option><option value="0">Rejeitar</option></select>
                <textarea name="rejection_reason" rows="4" class="block w-full rounded-md border-ink-200" placeholder="Motivo de rejeição, se aplicável"></textarea>
                <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Registar decisão</button>
            </form>
        </section>
    </div></div>
</x-app-layout>
