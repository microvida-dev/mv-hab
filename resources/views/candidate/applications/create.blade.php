<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Nova candidatura"
            :title="$contest->title"
            :description="$contest->program->name . ' · ' . $contest->code"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section
                title="Pré-verificação"
                description="Confirme que a informação preparatória está completa antes de criar o rascunho."
            >

                <div class="mt-5 divide-y divide-ink-100 border-y border-ink-100">
                    @foreach ($readiness['checks'] as $check)
                        <x-mv.check-card
                            :label="$check['label'] ?? $check['key'] ?? 'Verificação'"
                            :detail="$check['passed'] ? ($check['successMessage'] ?? null) : ($check['message'] ?? null)"
                            :passed="$check['passed']"
                        >
                            @if (! $check['passed'] && $check['route'])
                                <a href="{{ route($check['route']) }}" class="mt-3 inline-flex text-sm font-semibold text-mvhab-primary">
                                    Corrigir
                                </a>
                            @endif
                        </x-mv.check-card>
                    @endforeach
                </div>
            </x-mv.section>

            @if ($readiness['ready'])
                <form method="POST" action="{{ route('candidate.applications.store') }}" class="space-y-6">
                    @csrf
                    <x-mv.section title="Rascunho da candidatura">
                        <input type="hidden" name="contest_id" value="{{ $contest->id }}">

                        <div>
                            <x-input-label for="candidate_notes" value="Notas opcionais para preparação" />
                            <textarea id="candidate_notes" name="candidate_notes" rows="4" class="mv-input mt-1 block w-full">{{ old('candidate_notes') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('candidate_notes')" />
                        </div>

                        <x-mv.alert tone="success" class="mt-6">
                            A criação do rascunho não submete a candidatura. Poderá rever a documentação e aceitar as declarações no passo seguinte.
                        </x-mv.alert>
                    </x-mv.section>

                    <div class="flex flex-wrap justify-end gap-3">
                        <a href="{{ route('public.contests.show', $contest->slug) }}" class="mv-button-secondary">Cancelar</a>
                        <button type="submit" class="mv-button-primary">Criar rascunho</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
