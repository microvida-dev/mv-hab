<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiência prévia"
            title="Pronúncia submetida"
            :description="$submission->status->label()"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Pronúncia">
                <x-mv.badge>{{ $submission->status->label() }}</x-mv.badge>
                <p class="mt-5 text-sm leading-6 text-ink-700">{{ $submission->submission_text }}</p>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
