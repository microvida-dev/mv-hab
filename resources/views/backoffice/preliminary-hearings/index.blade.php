<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Audiências prévias"
            title="Audiências prévias submetidas"
            description="Analise pronúncias submetidas pelos candidatos durante a audiência prévia."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Pronúncias" padding="p-0" class="overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-5 py-4 font-semibold">{{ $submission->hearing?->subject }}</td>
                                <td class="px-5 py-4"><x-mv.badge>{{ $submission->status->label() }}</x-mv.badge></td>
                                <td class="px-5 py-4 text-right">
                                    <a class="font-semibold text-civic-700" href="{{ route('backoffice.preliminary-hearings.show', $submission) }}">Analisar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-8 text-center text-ink-500">Sem pronúncias.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-mv.section>

            {{ $submissions->links() }}
        </div>
    </div>
</x-app-layout>
