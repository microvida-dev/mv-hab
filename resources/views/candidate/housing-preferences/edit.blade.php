<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Candidatura · {{ $application->contest?->code }}"
            title="Fogos"
            description="Ordene todos os fogos compatíveis. A posição 1 corresponde à sua primeira preferência."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">
                    Voltar à candidatura
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    @php
        $compatibleIds = $compatibleOptions
            ->map(fn ($option) => (int) $option->unit->id)
            ->values();
        $oldOrderedIds = collect(old('preferences', []))
            ->filter(fn ($preference) => is_array($preference))
            ->sortBy(fn ($preference) => (int) ($preference['preference_order'] ?? PHP_INT_MAX))
            ->pluck('contest_housing_unit_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $compatibleIds->contains($id))
            ->unique()
            ->values();
        $existingOrderedIds = $application->housingPreferences
            ->sortBy('preference_order')
            ->pluck('contest_housing_unit_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $compatibleIds->contains($id))
            ->unique()
            ->values();
        $orderedIds = ($oldOrderedIds->isNotEmpty()
            ? $oldOrderedIds
            : $existingOrderedIds)
            ->concat($compatibleIds)
            ->unique()
            ->values();
        $orderedState = $orderedIds
            ->map(fn ($id) => (string) $id)
            ->all();
        $selectOptions = $compatibleOptions
            ->map(function ($option): array {
                $housingUnit = $option->unit->housingUnit;
                $title = $housingUnit?->displayTitle()
                    ?: $housingUnit?->public_reference
                    ?: 'Fogo compatível';
                $typology = data_get(
                    $option->compatibility->snapshot,
                    'typology',
                    $housingUnit?->typology,
                );
                $rent = data_get(
                    $option->compatibility->snapshot,
                    'monthly_rent',
                    $housingUnit?->monthly_rent,
                );

                return [
                    'id' => (string) $option->unit->id,
                    'label' => collect([
                        $title,
                        $typology,
                        is_numeric($rent)
                            ? number_format((float) $rent, 2, ',', ' ') . ' €'
                            : null,
                    ])->filter()->join(' · '),
                ];
            })
            ->values()
            ->all();
        $invalidatedPreferences = $application->housingPreferences
            ->filter(fn ($preference) => $preference->invalidated_at !== null
                || ! $compatibleIds->contains((int) $preference->contest_housing_unit_id));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @include('candidate.applications.partials.navigation', [
                'application' => $application,
            ])

            @if ($errors->any())
                <x-mv.alert tone="danger">
                    <p class="font-semibold">Não foi possível guardar a ordem dos fogos.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mv.alert>
            @endif

            @if ($invalidatedPreferences->isNotEmpty())
                <x-mv.alert tone="warning">
                    Os dados da candidatura ou a disponibilidade dos fogos foram alterados.
                    A ordem completa tem de ser confirmada novamente.
                </x-mv.alert>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumo de compatibilidade">
                <x-mv.stat-card
                    label="Agregado"
                    :value="$compatibilitySummary['household_members'] . ' membro(s)'"
                    hint="Composição usada nesta avaliação"
                />
                <x-mv.stat-card
                    label="Rendimento anual"
                    :value="$compatibilitySummary['annual_income'] !== null
                        ? number_format((float) $compatibilitySummary['annual_income'], 2, ',', ' ') . ' €'
                        : 'Dados em falta'"
                    :hint="$compatibilitySummary['annual_income_limit'] !== null
                        ? 'Limite aplicável: ' . number_format((float) $compatibilitySummary['annual_income_limit'], 2, ',', ' ') . ' €'
                        : 'Limite regulamentar indisponível'"
                />
                <x-mv.stat-card
                    label="Renda mensal máxima estimada"
                    :value="$compatibilitySummary['maximum_monthly_rent'] !== null
                        ? number_format((float) $compatibilitySummary['maximum_monthly_rent'], 2, ',', ' ') . ' €'
                        : 'Não calculável'"
                    :hint="$compatibilitySummary['maximum_effort_rate_percentage'] !== null
                        ? 'Taxa máxima: ' . $compatibilitySummary['maximum_effort_rate_percentage'] . '%'
                        : 'Taxa regulamentar indisponível'"
                />
                <x-mv.stat-card
                    label="Fogos a ordenar"
                    :value="$selectionConfiguration['required_count']"
                    hint="Todos os fogos compatíveis e ativos"
                />
            </section>

            <x-mv.section
                title="Ordem completa dos fogos"
                :description="$selectionConfiguration['required_count'] > 0
                    ? 'Existem ' . $selectionConfiguration['required_count'] . ' fogos compatíveis. Todos devem ocupar uma posição única, sem repetições nem omissões.'
                    : 'Não existem fogos compatíveis e ativos para ordenar neste momento.'"
            >
                <div class="flex flex-wrap gap-2">
                    <x-mv.badge tone="success">
                        {{ $compatibilitySummary['regulatory_regime'] ?? 'Regime a confirmar' }}
                    </x-mv.badge>
                    <x-mv.badge>
                        {{ $selectionConfiguration['required_count'] }} posição(ões)
                    </x-mv.badge>
                    <x-mv.badge>
                        Revalidação na submissão
                    </x-mv.badge>
                </div>
            </x-mv.section>

            @if (! $selectionConfiguration['enabled'])
                <x-mv.alert tone="warning">
                    A ordenação de fogos ainda não está configurada para este concurso.
                </x-mv.alert>
            @elseif (! $compatibilitySummary['configuration_complete'])
                <x-mv.alert tone="warning">
                    A configuração regulamentar necessária à seleção ainda não está completa.
                    Consulte as regras do concurso ou contacte os serviços municipais.
                </x-mv.alert>
            @elseif ($compatibleOptions->isEmpty())
                <x-mv.alert tone="warning">
                    <p class="font-semibold">Não existem fogos compatíveis disponíveis neste momento.</p>
                    <p class="mt-1">
                        Reveja o agregado e os rendimentos ou consulte as regras do concurso.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">Rever agregado</a>
                        <a href="{{ route('candidate.income-records.index') }}" class="mv-button-secondary">Rever rendimentos</a>
                        <a href="{{ route('public.contests.show', $application->contest?->slug) }}" class="mv-button-secondary">Consultar regras</a>
                    </div>
                </x-mv.alert>
            @else
                <form
                    method="POST"
                    action="{{ route('candidate.housing-preferences.update', $application) }}"
                    class="space-y-6"
                    x-data="{
                        orderedIds: @js($orderedState),
                        previousValue: null,
                        statusMessage: '',
                        change(index, value) {
                            const previous = this.previousValue ?? this.orderedIds[index];
                            const duplicate = this.orderedIds.findIndex(
                                (id, position) => position !== index && id === value
                            );

                            if (duplicate >= 0) {
                                this.orderedIds[duplicate] = previous;
                            }

                            this.orderedIds[index] = value;
                            this.previousValue = null;
                            this.statusMessage = `Posição ${index + 1} atualizada.`;
                        },
                        move(index, delta) {
                            const target = index + delta;

                            if (target < 0 || target >= this.orderedIds.length) return;

                            const current = this.orderedIds[index];
                            this.orderedIds[index] = this.orderedIds[target];
                            this.orderedIds[target] = current;
                            this.statusMessage = `Ordem atualizada. O fogo ocupa agora a posição ${target + 1}.`;
                        }
                    }"
                >
                    @csrf
                    @method('PATCH')

                    <p class="sr-only" aria-live="polite" x-text="statusMessage"></p>

                    <x-mv.section
                        title="Definir posições"
                        description="Escolha um fogo em cada posição. Ao repetir um fogo, as duas posições são automaticamente trocadas."
                    >
                        <ol class="space-y-4">
                            @foreach ($orderedIds as $index => $orderedId)
                                <li class="rounded-2xl border border-ink-100 bg-white p-4 shadow-sm">
                                    <div class="grid gap-4 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-end">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-mvhab-surface text-lg font-semibold text-mvhab-primary" aria-hidden="true">
                                            {{ $index + 1 }}
                                        </span>

                                        <label class="text-sm font-semibold text-ink-900">
                                            Posição {{ $index + 1 }}
                                            <select
                                                name="preferences[{{ $index }}][contest_housing_unit_id]"
                                                class="mv-input mt-2 w-full"
                                                x-model="orderedIds[{{ $index }}]"
                                                @focus="previousValue = orderedIds[{{ $index }}]"
                                                @change="change({{ $index }}, $event.target.value)"
                                                required
                                            >
                                                @foreach ($selectOptions as $selectOption)
                                                    <option
                                                        value="{{ $selectOption['id'] }}"
                                                        @selected((string) $orderedId === $selectOption['id'])
                                                    >
                                                        {{ $selectOption['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input
                                                type="hidden"
                                                name="preferences[{{ $index }}][preference_order]"
                                                value="{{ $index + 1 }}"
                                            >
                                        </label>

                                        <div class="flex gap-2" aria-label="Alterar a posição {{ $index + 1 }}">
                                            <button
                                                type="button"
                                                class="mv-button-secondary px-3"
                                                aria-label="Mover o fogo da posição {{ $index + 1 }} para cima"
                                                @click="move({{ $index }}, -1)"
                                                @disabled($loop->first)
                                            >↑</button>
                                            <button
                                                type="button"
                                                class="mv-button-secondary px-3"
                                                aria-label="Mover o fogo da posição {{ $index + 1 }} para baixo"
                                                @click="move({{ $index }}, 1)"
                                                @disabled($loop->last)
                                            >↓</button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </x-mv.section>

                    <x-mv.alert>
                        Guardar a ordem não reserva nenhum fogo. Na submissão da candidatura,
                        o servidor volta a validar o conjunto completo, a disponibilidade, a
                        compatibilidade e a ordem antes de bloquear o snapshot final.
                    </x-mv.alert>

                    <div class="flex flex-wrap justify-end gap-3">
                        <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="mv-button-primary">
                            Guardar ordem dos fogos
                        </button>
                    </div>
                </form>

                <x-mv.section
                    title="Fogos compatíveis"
                    description="Informação pública utilizada para apoiar a definição da ordem."
                >
                    <div class="grid gap-5 lg:grid-cols-2">
                        @foreach ($compatibleOptions as $option)
                            @php
                                $unit = $option->unit;
                                $housingUnit = $unit->housingUnit;
                                $snapshot = $option->compatibility->snapshot;
                                $cover = $housingUnit?->coverImage;
                                $coverUrl = $cover
                                    ? \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path)
                                    : null;
                            @endphp

                            <article class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-sm">
                                @if ($coverUrl)
                                    <img
                                        src="{{ $coverUrl }}"
                                        alt="{{ $cover->alt_text ?: $housingUnit?->displayTitle() }}"
                                        class="h-44 w-full object-cover"
                                    >
                                @endif

                                <div class="space-y-4 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-mvhab-primary">
                                                {{ $housingUnit?->public_reference }}
                                            </p>
                                            <h2 class="mt-1 text-lg font-semibold text-ink-900">
                                                {{ $housingUnit?->displayTitle() }}
                                            </h2>
                                            <p class="mt-1 text-sm text-ink-500">
                                                {{ $housingUnit?->publicLocationLabel() }}
                                            </p>
                                        </div>
                                        <x-mv.badge tone="success">Compatível</x-mv.badge>
                                    </div>

                                    <dl class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <dt class="text-ink-500">Tipologia</dt>
                                            <dd class="mt-1 font-semibold text-ink-900">{{ $snapshot['typology'] ?? '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-ink-500">Renda mensal</dt>
                                            <dd class="mt-1 font-semibold text-ink-900">
                                                {{ isset($snapshot['monthly_rent'])
                                                    ? number_format((float) $snapshot['monthly_rent'], 2, ',', ' ') . ' €'
                                                    : '—' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-ink-500">Taxa de esforço estimada</dt>
                                            <dd class="mt-1 font-semibold text-ink-900">
                                                {{ isset($snapshot['calculated_effort_rate_percentage'])
                                                    ? number_format((float) $snapshot['calculated_effort_rate_percentage'], 2, ',', ' ') . '%'
                                                    : '—' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-ink-500">Ocupação</dt>
                                            <dd class="mt-1 font-semibold text-ink-900">
                                                {{ $unit->min_occupants ?? 1 }}–{{ $unit->max_occupants ?? '—' }} pessoas
                                            </dd>
                                        </div>
                                    </dl>

                                    <a
                                        href="{{ route('public.housing-units.show', $housingUnit?->public_slug) }}"
                                        class="text-sm font-semibold text-mvhab-primary hover:underline"
                                    >
                                        Ver ficha pública
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </x-mv.section>
            @endif
        </div>
    </div>
</x-app-layout>
