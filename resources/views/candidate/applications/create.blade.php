<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Nova candidatura</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $contest->title }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $contest->program->name }} · {{ $contest->code }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Pré-verificação</h2>
                <p class="mt-2 text-sm leading-6 text-ink-600">Confirme que a informação preparatória está completa antes de criar o rascunho.</p>

                <div class="mt-5 divide-y divide-ink-100 border-y border-ink-100">
                    @foreach ($readiness['checks'] as $check)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-2xl {{ $check['passed'] ? 'bg-mvhab-surface text-mvhab-primary' : 'bg-red-50 text-red-700' }}">
                                    <x-ui-icon :name="$check['passed'] ? 'check' : 'alert'" class="h-3.5 w-3.5" />
                                </span>
                                    <p class="text-sm text-ink-700">{{ $check['passed'] ? $check['successMessage'] : $check['message'] }}</p>
                            </div>
                            @if (! $check['passed'] && $check['route'])
                                <a href="{{ route($check['route'], $check['routeParameters']) }}" class="text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">Corrigir</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($readiness['ready'])
                <form method="POST" action="{{ route('candidate.applications.store') }}" class="mv-surface space-y-6 p-6">
                    @csrf
                    <input type="hidden" name="contest_id" value="{{ $contest->id }}">

                    <div>
                        <x-input-label for="candidate_notes" value="Notas opcionais para preparação" />
                        <textarea id="candidate_notes" name="candidate_notes" rows="4" class="mv-input mt-1 block w-full">{{ old('candidate_notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('candidate_notes')" />
                    </div>

                    <div class="rounded-2xl bg-mvhab-surface p-4 text-sm leading-6 text-mvhab-primary">
                        A criação do rascunho não submete a candidatura. Poderá rever a documentação e aceitar as declarações no passo seguinte.
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <a href="{{ route('public.contests.show', $contest->slug) }}" class="mv-button-secondary">Cancelar</a>
                        <button type="submit" class="mv-button-primary">Criar rascunho</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
