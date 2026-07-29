<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Candidatura · {{ $application->contest?->code }}"
            title="Habitações pretendidas"
            description="Selecione apenas as habitações onde pretende efetivamente concorrer e indique a ordem de preferência."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">
                    Voltar à candidatura
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    @php
        $oldPreferences = collect(old('preferences', []))
            ->filter(fn ($preference) => is_array($preference))
            ->keyBy(fn ($preference) => (string) ($preference['contest_housing_unit_id'] ?? ''));
        $existingByUnit = $application->housingPreferences
            ->keyBy(fn ($preference) => (string) $preference->contest_housing_unit_id);
        $selectedState = [];
        $orderState = [];

        foreach ($compatibleOptions as $option) {
            $key = (string) $option->unit->id;
            $oldPreference = $oldPreferences->get($key)
                ?? $oldPreferences->firstWhere('contest_housing_unit_id', $option->unit->id);
            $existing = $existingByUnit->get($key);
            $selectedState[$key] = $oldPreference !== null || $existing !== null;
            $orderState[$key] = (int) data_get(
                $oldPreference,
                'preference_order',
                $existing?->preference_order ?? 1,
            );
        }

        $compatibleUnitIds = $compatibleOptions
            ->map(fn ($option) => $option->unit->id)
            ->all();
        $invalidatedPreferences = $application->housingPreferences
            ->filter(fn ($preference) => $preference->invalidated_at !== null
                || ! in_array($preference->contest_housing_unit_id, $compatibleUnitIds, true));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($errors->any())
                <x-mv.alert tone="danger">
                    <p class="font-semibold">Não foi possível guardar a seleção.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mv.alert>
            @endif

            @if ($invalidatedPreferences->isNotEmpty())
                <x-mv.alert tone="warning">
                    Os dados do agregado ou dos rendimentos foram alterados, ou uma habitação deixou de ser selecionável.
                    Confirme novamente as habitações pretendidas.
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
                    label="Opções compatíveis"
                    :value="$compatibleOptions->count()"
                    :hint="collect($compatibilitySummary['adequate_typologies'])->isNotEmpty()
                        ? 'Tipologias aplicáveis: ' . collect($compatibilitySummary['adequate_typologies'])->join(', ')
                        : 'Tipologia sujeita a validação'"
                />
            </section>

            <x-mv.section
                title="Regras da seleção"
                :description="$selectionConfiguration['required']
                    ? 'Escolha entre ' . $selectionConfiguration['minimum'] . ' e ' . $selectionConfiguration['maximum'] . ' habitações. A ordem 1 corresponde à sua primeira preferência.'
                    : 'A seleção é opcional neste concurso. Pode escolher até ' . $selectionConfiguration['maximum'] . ' habitações.'"
            >
                <div class="flex flex-wrap gap-2">
                    <x-mv.badge tone="success">
                        {{ $compatibilitySummary['regulatory_regime'] ?? 'Regime a confirmar' }}
                    </x-mv.badge>
                    <x-mv.badge>
                        Mínimo {{ $selectionConfiguration['minimum'] }}
                    </x-mv.badge>
                    <x-mv.badge>
                        Máximo {{ $selectionConfiguration['maximum'] }}
                    </x-mv.badge>
                </div>
            </x-mv.section>

            @if (! $selectionConfiguration['enabled'])
                <x-mv.alert tone="warning">
                    A seleção de habitações ainda não está configurada para este concurso.
                </x-mv.alert>
            @elseif (! $compatibilitySummary['configuration_complete'])
                <x-mv.alert tone="warning">
                    A configuração regulamentar necessária à seleção ainda não está completa.
                    Consulte as regras do concurso ou contacte os serviços municipais.
                </x-mv.alert>
            @endif

            <form
                method="POST"
                action="{{ route('candidate.housing-preferences.update', $application) }}"
                class="space-y-6"
                x-data="{
                    selected: @js($selectedState),
                    orders: @js($orderState),
                    maximum: {{ $selectionConfiguration['maximum'] }},
                    dragged: null,
                    selectionError: '',
                    statusMessage: '',
                    selectedCount() {
                        return Object.values(this.selected).filter(Boolean).length;
                    },
                    normalizeOrders() {
                        Object.keys(this.orders)
                            .filter(key => this.selected[key])
                            .sort((a, b) => Number(this.orders[a] || 999) - Number(this.orders[b] || 999))
                            .forEach((key, index) => this.orders[key] = index + 1);
                    },
                    toggle(id) {
                        this.selectionError = '';
                        if (this.selected[id] && this.selectedCount() > this.maximum) {
                            this.selected[id] = false;
                            this.selectionError = `Pode selecionar no máximo ${this.maximum} habitações.`;
                            this.statusMessage = this.selectionError;
                            return;
                        }
                        if (this.selected[id]) {
                            const used = Object.keys(this.orders)
                                .filter(key => key !== id && this.selected[key])
                                .map(key => Number(this.orders[key]));
                            let order = 1;
                            while (used.includes(order)) order++;
                            this.orders[id] = order;
                        }
                        this.normalizeOrders();
                        this.statusMessage = this.selected[id]
                            ? `Habitação selecionada como preferência ${this.orders[id]}.`
                            : 'Habitação removida da seleção.';
                    },
                    move(id, delta) {
                        if (!this.selected[id]) return;
                        const current = Number(this.orders[id] || 1);
                        const target = current + delta;
                        if (target < 1 || target > this.maximum) return;
                        const other = Object.keys(this.orders).find(
                            key => key !== id && this.selected[key] && Number(this.orders[key]) === target
                        );
                        if (other) this.orders[other] = current;
                        this.orders[id] = target;
                        this.statusMessage = `Ordem atualizada: preferência ${target}.`;
                    },
                    startDrag(id) {
                        if (this.selected[id]) this.dragged = id;
                    },
                    dropOn(id) {
                        if (!this.dragged || this.dragged === id || !this.selected[id]) return;
                        const draggedOrder = Number(this.orders[this.dragged]);
                        this.orders[this.dragged] = Number(this.orders[id]);
                        this.orders[id] = draggedOrder;
                        this.statusMessage = `Ordem atualizada: preferência ${this.orders[id]}.`;
                        this.dragged = null;
                    }
                }"
            >
                @csrf
                @method('PATCH')

                <p
                    class="sr-only"
                    aria-live="polite"
                    x-text="statusMessage"
                ></p>

                <x-mv.section
                    title="Habitações compatíveis"
                    description="A seleção não reserva a habitação. A disponibilidade volta a ser confirmada quando submeter a candidatura."
                >
                    <div class="grid gap-5 lg:grid-cols-2">
                        @forelse ($compatibleOptions as $option)
                            @php
                                $unit = $option->unit;
                                $housingUnit = $unit->housingUnit;
                                $key = (string) $unit->id;
                                $cover = $housingUnit?->coverImage;
                                $coverUrl = $cover
                                    ? \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path)
                                    : null;
                                $snapshot = $option->compatibility->snapshot;
                            @endphp

                            <article
                                class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-sm focus-within:ring-2 focus-within:ring-mvhab-primary"
                                aria-labelledby="housing-option-{{ $unit->id }}"
                                :draggable="selected['{{ $key }}']"
                                @dragstart="startDrag('{{ $key }}')"
                                @dragover.prevent
                                @drop.prevent="dropOn('{{ $key }}')"
                            >
                                @if ($coverUrl)
                                    <img
                                        src="{{ $coverUrl }}"
                                        alt="{{ $cover->alt_text ?: $housingUnit?->displayTitle() }}"
                                        class="h-44 w-full object-cover"
                                    >
                                @endif

                                <div class="space-y-5 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-mvhab-primary">
                                                {{ $housingUnit?->public_reference }}
                                            </p>
                                            <h2 id="housing-option-{{ $unit->id }}" class="mt-1 text-lg font-semibold text-ink-900">
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
                                            <dt class="text-ink-500">Quartos</dt>
                                            <dd class="mt-1 font-semibold text-ink-900">{{ $unit->bedrooms ?? $housingUnit?->bedrooms ?? '—' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-ink-500">Área útil</dt>
                                            <dd class="mt-1 font-semibold text-ink-900">
                                                {{ $housingUnit?->usable_area_sqm
                                                    ? number_format((float) $housingUnit->usable_area_sqm, 2, ',', ' ') . ' m²'
                                                    : '—' }}
                                            </dd>
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

                                    @if ($housingUnit?->publicFeatures->isNotEmpty())
                                        <ul class="flex flex-wrap gap-2" aria-label="Características">
                                            @foreach ($housingUnit->publicFeatures->take(4) as $feature)
                                                <li><x-mv.badge>{{ $feature->label }}</x-mv.badge></li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <div class="flex flex-wrap gap-3">
                                        <a
                                            href="{{ route('public.housing-units.show', $housingUnit?->public_slug) }}"
                                            class="text-sm font-semibold text-mvhab-primary hover:underline"
                                        >
                                            Ver ficha pública
                                        </a>
                                    </div>

                                    <div class="rounded-2xl bg-ink-50 p-4">
                                        <label class="flex cursor-pointer items-start gap-3 font-semibold text-ink-900">
                                            <input
                                                type="checkbox"
                                                name="preferences[{{ $unit->id }}][contest_housing_unit_id]"
                                                value="{{ $unit->id }}"
                                                class="mv-checkbox mt-0.5"
                                                x-model="selected['{{ $key }}']"
                                                @change="toggle('{{ $key }}'); $nextTick(() => $el.focus())"
                                            >
                                            <span>Selecionar esta habitação</span>
                                        </label>

                                        <div class="mt-4 grid grid-cols-[minmax(0,1fr)_auto] items-end gap-3">
                                            <label class="text-sm font-medium text-ink-700">
                                                Ordem de preferência
                                                <input
                                                    type="number"
                                                    name="preferences[{{ $unit->id }}][preference_order]"
                                                    min="1"
                                                    max="{{ max(1, $selectionConfiguration['maximum']) }}"
                                                    class="mv-input mt-1 w-full"
                                                    x-model.number="orders['{{ $key }}']"
                                                    :disabled="!selected['{{ $key }}']"
                                                    aria-describedby="preference-order-help-{{ $unit->id }}"
                                                    @keydown.alt.arrow-up.prevent="move('{{ $key }}', -1)"
                                                    @keydown.alt.arrow-down.prevent="move('{{ $key }}', 1)"
                                                >
                                                <span id="preference-order-help-{{ $unit->id }}" class="sr-only">
                                                    Use Alt e seta para cima ou para baixo para alterar a ordem.
                                                </span>
                                            </label>
                                            <div class="flex gap-2" aria-label="Alterar ordem de {{ $housingUnit?->displayTitle() }}">
                                                <button
                                                    type="button"
                                                    class="mv-button-secondary px-3"
                                                    aria-label="Subir {{ $housingUnit?->displayTitle() }} na ordem"
                                                    @click="move('{{ $key }}', -1)"
                                                    :disabled="!selected['{{ $key }}']"
                                                >↑</button>
                                                <button
                                                    type="button"
                                                    class="mv-button-secondary px-3"
                                                    aria-label="Descer {{ $housingUnit?->displayTitle() }} na ordem"
                                                    @click="move('{{ $key }}', 1)"
                                                    :disabled="!selected['{{ $key }}']"
                                                >↓</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="lg:col-span-2">
                                <x-mv.alert tone="warning">
                                    <p class="font-semibold">Não existem habitações compatíveis disponíveis neste momento.</p>
                                    <p class="mt-1">
                                        Reveja o agregado e os rendimentos, consulte as regras do concurso ou contacte o apoio municipal.
                                    </p>
                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">Rever agregado</a>
                                        <a href="{{ route('candidate.income-records.index') }}" class="mv-button-secondary">Rever rendimentos</a>
                                        <a href="{{ route('public.contests.show', $application->contest?->slug) }}" class="mv-button-secondary">Consultar regras</a>
                                    </div>
                                </x-mv.alert>
                            </div>
                        @endforelse
                    </div>
                </x-mv.section>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="mv-button-primary">
                        Guardar seleção
                    </button>
                </div>
            </form>

            @if ($preferenceReadiness['passed'] && $application->housingPreferences->isNotEmpty())
                <x-mv.section
                    title="Confirmar habitações pretendidas"
                    description="Esta confirmação não reserva as habitações. A seleção será novamente validada quando submeter a candidatura."
                >
                    <form method="POST" action="{{ route('candidate.housing-preferences.submit', $application) }}">
                        @csrf
                        @foreach ($application->housingPreferences as $index => $preference)
                            <input
                                type="hidden"
                                name="preferences[{{ $index }}][contest_housing_unit_id]"
                                value="{{ $preference->contest_housing_unit_id }}"
                            >
                            <input
                                type="hidden"
                                name="preferences[{{ $index }}][preference_order]"
                                value="{{ $preference->preference_order }}"
                            >
                        @endforeach
                        <button type="submit" class="mv-button-primary">
                            Confirmar seleção
                        </button>
                    </form>
                </x-mv.section>
            @endif
        </div>
    </div>
</x-app-layout>
