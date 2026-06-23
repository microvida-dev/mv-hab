<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Pronúncia submetida</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><section class="mv-surface p-6"><p class="text-sm font-semibold text-ink-900">{{ $submission->status->label() }}</p><p class="mt-3 text-sm text-ink-600">{{ $submission->submission_text }}</p></section></div></div>
</x-app-layout>
