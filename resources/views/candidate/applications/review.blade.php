<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Revisão final</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Rever e submeter candidatura</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $application->contest->title }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="rounded-2xl border border-signal-200 bg-signal-50 p-5 text-sm leading-6 text-signal-900">
                Antes de submeter, confirme cuidadosamente todos os dados. Após a submissão, a candidatura ficará bloqueada para edição direta e será analisada pelos serviços municipais.
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Estado da preparação</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($readiness['checks'] as $check)
                        <div class="flex items-start gap-3 py-4">
                            <span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-2xl {{ $check['passed'] ? 'bg-mvhab-surface text-mvhab-primary' : 'bg-red-50 text-red-700' }}">
                                <x-ui-icon :name="$check['passed'] ? 'check' : 'alert'" class="h-3.5 w-3.5" />
                            </span>
                            <p class="text-sm text-ink-700">{{ $check['passed'] ? $check['successMessage'] : $check['message'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Dados pessoais</p>
                    <p class="mt-2 font-semibold text-ink-900">{{ $application->adhesionRegistration->full_name }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $application->adhesionRegistration->email }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Agregado</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $application->household->members->count() }}</p>
                    <p class="mt-1 text-sm text-ink-500">membro(s)</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Rendimentos</p>
                    <p class="mt-2 text-xl font-semibold text-ink-900">{{ number_format($application->household->incomeRecords->sum('monthly_amount'), 2, ',', '.') }} €</p>
                    <p class="mt-1 text-sm text-ink-500">mensais declarados</p>
                </div>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documentos</h2>
                    <p class="mt-1 text-sm text-ink-500">{{ $readiness['documents']['summary']['submitted'] }} de {{ $readiness['documents']['summary']['total_required'] }} documentos obrigatórios submetidos ou validados.</p>
                </div>
                <div class="divide-y divide-ink-100">
                    @foreach ($readiness['documents']['items'] as $item)
                        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $item['document_type']->name }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $item['target_label'] }} · {{ $item['status']->label() }}</p>
                            </div>
                            @if ($item['submission'])
                                <a href="{{ route('candidate.documents.show', $item['submission']) }}" class="text-sm font-semibold text-mvhab-primary">Consultar</a>
                            @else
                                <a href="{{ route('candidate.documents.create', [
                                    'application' => $application->public_id,
                                    'item' => $item['key'],
                                    'required_document_id' => $item['required_document_id'],
                                    'target_type' => $item['target_type'],
                                    'target_id' => $item['target_id'],
                                ]) }}" class="mv-button-secondary">Submeter</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <form method="POST" action="{{ route('candidate.applications.submit', $application) }}" class="mv-surface space-y-5 p-6">
                @csrf
                <h2 class="text-lg font-semibold text-ink-900">Declarações obrigatórias</h2>

                @foreach ([
                    'declaration_accepted' => 'Declaro, sob compromisso de honra, que todas as informações prestadas correspondem à verdade.',
                    'contest_rules_accepted' => 'Declaro que tomei conhecimento das regras do concurso e do programa aplicável.',
                    'data_processing_accepted' => 'Autorizo o tratamento dos dados pessoais necessários à análise e gestão da candidatura.',
                    'truthfulness_accepted' => 'Confirmo a veracidade da informação e dos documentos apresentados.',
                    'data_current_confirmed' => 'Confirmo que os dados do registo, agregado, rendimentos, situação habitacional e documentos estão corretos e atualizados.',
                ] as $field => $label)
                    <label class="flex items-start gap-3 rounded-2xl border border-ink-100 p-4">
                        <input type="checkbox" name="{{ $field }}" value="1" class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary" {{ old($field) ? 'checked' : '' }}>
                        <span class="text-sm leading-6 text-ink-700">{{ $label }}</span>
                    </label>
                @endforeach

                <p class="text-xs leading-5 text-ink-500">{{ $readiness['eligibility_pre_check']['message'] }}</p>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">Voltar ao rascunho</a>
                    <button type="submit" class="mv-button-primary" {{ $readiness['ready'] ? '' : 'disabled' }}>Submeter candidatura</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
