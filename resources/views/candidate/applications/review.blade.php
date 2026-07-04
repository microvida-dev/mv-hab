<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Revisão final"
            title="Rever e submeter candidatura"
            :description="$application->contest->title"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.alert tone="warning">
                Antes de submeter, confirme cuidadosamente todos os dados. Após a submissão, a candidatura ficará bloqueada para edição direta e será analisada pelos serviços municipais.
            </x-mv.alert>

            <x-mv.section title="Estado da preparação">
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($readiness['checks'] as $check)
                        <x-mv.check-card
                            :label="$check['label'] ?? $check['key'] ?? 'Verificação'"
                            :detail="$check['detail'] ?? null"
                            :passed="$check['passed']"
                        />
                    @endforeach
                </div>
            </x-mv.section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-mv.stat-card
                    label="Dados pessoais"
                    :value="$application->adhesionRegistration->full_name"
                    :hint="$application->adhesionRegistration->email"
                />
                <x-mv.stat-card
                    label="Agregado"
                    :value="$application->household->members->count()"
                    hint="membro(s)"
                />
                <x-mv.stat-card
                    label="Rendimentos"
                    :value="number_format($application->household->incomeRecords->sum('monthly_amount'), 2, ',', '.') . ' €'"
                    hint="mensais declarados"
                />
            </section>

            <x-mv.section
                title="Documentos"
                :description="$readiness['documents']['summary']['submitted'] . ' de ' . $readiness['documents']['summary']['total_required'] . ' documentos obrigatórios submetidos ou validados.'"
                class="overflow-hidden"
            >
                <div class="divide-y divide-ink-100">
                    @foreach ($readiness['documents']['items'] as $item)
                        <div class="flex flex-wrap items-center justify-between gap-4 py-4">
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
            </x-mv.section>

            <form method="POST" action="{{ route('candidate.applications.submit', $application) }}" class="space-y-5">
                @csrf
                <x-mv.section title="Declarações obrigatórias">

                    @foreach ([
                        'declaration_accepted' => 'Declaro, sob compromisso de honra, que todas as informações prestadas correspondem à verdade.',
                        'contest_rules_accepted' => 'Declaro que tomei conhecimento das regras do concurso e do programa aplicável.',
                        'data_processing_accepted' => 'Autorizo o tratamento dos dados pessoais necessários à análise e gestão da candidatura.',
                        'truthfulness_accepted' => 'Confirmo a veracidade da informação e dos documentos apresentados.',
                        'data_current_confirmed' => 'Confirmo que os dados do registo, agregado, rendimentos, situação habitacional e documentos estão corretos e atualizados.',
                    ] as $field => $label)
                        <x-mv.checkbox-card
                            :name="$field"
                            :label="$label"
                            :checked="old($field)"
                            align="start"
                            class="mt-3"
                        />
                    @endforeach

                    <p class="mt-5 text-xs leading-5 text-ink-500">{{ $readiness['eligibility_pre_check']['message'] }}</p>
                </x-mv.section>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">Voltar ao rascunho</a>
                    <button type="submit" class="mv-button-primary" {{ $readiness['ready'] ? '' : 'disabled' }}>Submeter candidatura</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
