<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Procedimento"
            title="Atas do procedimento"
            description="Gere atas internas a partir dos dados reais do concurso, candidaturas, listas, reclamações e sorteios."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section
                title="Gerar ata"
                description="A ata é preparada automaticamente a partir do snapshot processual e deve ser revista, validada e aprovada pelos responsáveis competentes."
            >
                <form method="POST" action="{{ route('backoffice.procedure-minutes.generate') }}" class="grid gap-4 md:grid-cols-2">
                    @csrf

                    <label class="text-sm font-semibold text-ink-700">
                        Minuta
                        <select name="procedure_template_id" class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary" required>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}" @selected(old('procedure_template_id') == $template->id)>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Assunto
                        <input
                            name="subject"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('subject', 'Reunião de acompanhamento do procedimento') }}"
                            required
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Concurso
                        <select name="contest_id" class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Selecionar concurso</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}" @selected(old('contest_id') == $contest->id)>
                                    {{ $contest->code }} · {{ $contest->title }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Candidatura
                        <select name="application_id" class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Sem candidatura específica</option>
                            @foreach ($applications as $application)
                                <option value="{{ $application->id }}" @selected(old('application_id') == $application->id)>
                                    {{ $application->application_number }} · {{ $application->user?->name ?? 'Candidato' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Data da reunião
                        <input
                            type="date"
                            name="meeting_date"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('meeting_date') }}"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Hora
                        <input
                            type="time"
                            name="meeting_time"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('meeting_time') }}"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Local
                        <input
                            name="meeting_location"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('meeting_location') }}"
                            placeholder="Paços do Concelho de Alcanena"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Título
                        <input
                            name="title"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('title', 'Ata do procedimento') }}"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Número de registo
                        <input
                            name="municipal_registry_number"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('municipal_registry_number') }}"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700">
                        Número de processo municipal
                        <input
                            name="municipal_process_number"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('municipal_process_number') }}"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700 md:col-span-2">
                        Referência externa
                        <input
                            name="external_reference"
                            class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            value="{{ old('external_reference') }}"
                        >
                    </label>

                    <label class="text-sm font-semibold text-ink-700 md:col-span-2">
                        Enquadramento legal
                        <textarea name="legal_basis" rows="4" class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">{{ old('legal_basis') }}</textarea>
                    </label>

                    <label class="text-sm font-semibold text-ink-700 md:col-span-2">
                        Deliberação
                        <textarea name="deliberation_text" rows="5" class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">{{ old('deliberation_text') }}</textarea>
                    </label>

                    <label class="text-sm font-semibold text-ink-700 md:col-span-2">
                        Observações
                        <textarea name="observations" rows="4" class="mt-1 w-full rounded-xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary">{{ old('observations') }}</textarea>
                    </label>

                    <details open class="md:col-span-2 rounded-2xl border border-ink-100 bg-ink-50 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-ink-800">
                            Membros da ata
                        </summary>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-semibold text-ink-700">
                                Presidente — Nome
                                <input name="jury_president_name" class="mt-1 w-full rounded-md border-ink-200" value="Ana Cristina dos Santos Vilaverde Carneiro">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Presidente — Cargo/Função
                                <input name="jury_president_role" class="mt-1 w-full rounded-md border-ink-200" value="Técnica Superior Jurista">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Vogal 1 — Nome
                                <input name="jury_vogal_1_name" class="mt-1 w-full rounded-md border-ink-200" value="Andreia Abreu do Rosário">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Vogal 1 — Cargo/Função
                                <input name="jury_vogal_1_role" class="mt-1 w-full rounded-md border-ink-200" value="Técnica Superior em Geografia e Gestão do Território a exercer funções na DDSU - Divisão de Desenvolvimento Sustentável e Urbanismo">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Vogal 2 — Nome
                                <input name="jury_vogal_2_name" class="mt-1 w-full rounded-md border-ink-200" value="Fernando Marques Tomás">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Vogal 2 — Cargo/Função
                                <input name="jury_vogal_2_role" class="mt-1 w-full rounded-md border-ink-200" value="Chefe da DPGOM - Divisão de Planeamento e Gestão de Obras Municipais">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Vogal 3 — Nome
                                <input name="jury_vogal_3_name" class="mt-1 w-full rounded-md border-ink-200" value="Maria João Café Ferreira">
                            </label>

                            <label class="text-sm font-semibold text-ink-700">
                                Vogal 3 — Cargo/Função
                                <input name="jury_vogal_3_role" class="mt-1 w-full rounded-md border-ink-200" value="Dirigente intermédia de 3.º grau da SGFPCO - Subunidade de Gestão Financeira, Patrimonial e Controlo Orçamental">
                            </label>
                        </div>
                    </details>

                    <div class="md:col-span-2">
                        <button class="mv-button-primary" type="submit">Gerar ata</button>
                    </div>
                </form>
            </x-mv.section>

            <x-mv.section title="Atas geradas">
                <div class="overflow-x-auto">
                    <table class="mv-table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Título</th>
                                <th>Concurso</th>
                                <th>Estado</th>
                                <th>Gerada em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($minutes as $minute)
                                <tr>
                                    <td class="font-mono text-xs">{{ $minute->minute_number }}</td>
                                    <td>{{ $minute->title }}</td>
                                    <td>{{ $minute->contest?->code ?? '—' }}</td>
                                    <td>
                                        <x-mv.badge :tone="$minute->approved_at ? 'success' : 'neutral'">
                                            {{ $minute->status->label() }}
                                        </x-mv.badge>
                                    </td>
                                    <td>{{ $minute->generated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-3">
                                            <a
                                                href="{{ route('backoffice.procedure-minutes.show', $minute) }}"
                                                class="font-semibold text-mvhab-primary hover:underline"
                                            >
                                                Abrir
                                            </a>

                                            @can('delete', $minute)
                                                <form
                                                    method="POST"
                                                    action="{{ route('backoffice.procedure-minutes.destroy', $minute) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('Tem a certeza que pretende eliminar esta ata?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="font-semibold text-red-600 hover:text-red-700"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">
                                        Sem atas geradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $minutes->links() }}
                </div>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
