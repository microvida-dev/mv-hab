<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamações"
            title="Editar reclamação"
            :description="$complaint->complaint_number"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.complaints.update', $complaint) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <x-mv.section title="Fundamentos">
                    <div class="space-y-5">
                        <div>
                            <x-input-label for="subject" value="Assunto" />
                            <x-text-input id="subject" name="subject" class="mt-1 w-full" value="{{ old('subject', $complaint->subject) }}" required />
                        </div>

                        <div>
                            <x-input-label for="grounds" value="Fundamentos" />
                            <textarea id="grounds" name="grounds" class="mv-input mt-1 w-full" required>{{ old('grounds', $complaint->grounds) }}</textarea>
                        </div>

                        <div>
                            <x-input-label for="requested_outcome" value="Resultado pretendido" />
                            <textarea id="requested_outcome" name="requested_outcome" class="mv-input mt-1 w-full">{{ old('requested_outcome', $complaint->requested_outcome) }}</textarea>
                        </div>
                    </div>
                </x-mv.section>

                <button class="mv-button-primary">Guardar</button>
            </form>
        </div>
    </div>
</x-app-layout>
