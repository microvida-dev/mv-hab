<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Administração da plataforma"
            title="Contexto municipal"
            description="Selecione de forma explícita e auditada o Município em que pretende operar. A identidade global permanece inalterada."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($errors->any())
                <section
                    class="rounded-3xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"
                    role="alert"
                    aria-labelledby="municipal-context-errors-title"
                >
                    <h2 id="municipal-context-errors-title" class="font-semibold">
                        Não foi possível alterar o contexto municipal.
                    </h2>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <x-mv.alert>
                A seleção não altera o utilizador, as roles, as permissions nem as funcionalidades contratadas pelo Município. Não inclua dados pessoais na justificação.
            </x-mv.alert>

            <section class="mv-surface p-5 sm:p-6" aria-labelledby="current-context-title">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-mvhab-primary">
                            Estado atual
                        </p>
                        <h2 id="current-context-title" class="mt-1 text-lg font-semibold text-ink-950">
                            @if ($currentMunicipality)
                                {{ $currentMunicipality->name }}
                            @else
                                Sem contexto municipal ativo
                            @endif
                        </h2>

                        @if ($currentMunicipality)
                            <p class="mt-2 text-sm text-ink-600">
                                Código institucional: {{ $currentMunicipality->code }}
                            </p>
                        @else
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">
                                Enquanto não selecionar um Município, os módulos, indicadores e registos municipais permanecem indisponíveis.
                            </p>
                        @endif
                    </div>

                    @if ($currentMunicipality)
                        <form
                            method="POST"
                            action="{{ route('backoffice.platform.municipal-context.destroy') }}"
                            class="w-full max-w-xl rounded-3xl border border-ink-100 bg-ink-50/60 p-4"
                        >
                            @csrf
                            @method('DELETE')

                            <label
                                for="clear-context-justification"
                                class="block text-sm font-semibold text-ink-900"
                            >
                                Justificação para sair do contexto
                            </label>
                            <textarea
                                id="clear-context-justification"
                                name="justification"
                                rows="3"
                                required
                                minlength="10"
                                maxlength="500"
                                class="mt-2 w-full rounded-2xl border-ink-200 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >{{ old('justification') }}</textarea>

                            <label class="mt-3 flex items-start gap-3 text-sm text-ink-700">
                                <input
                                    type="checkbox"
                                    name="confirm"
                                    value="1"
                                    required
                                    class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary"
                                >
                                <span>Confirmo que pretendo terminar o contexto municipal atual.</span>
                            </label>

                            <button type="submit" class="mv-button-secondary mt-4">
                                Sair do contexto municipal
                            </button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="mv-surface p-5 sm:p-6" aria-labelledby="municipality-filter-title">
                <h2 id="municipality-filter-title" class="text-lg font-semibold text-ink-950">
                    Pesquisar Municípios
                </h2>

                <form method="GET" action="{{ route('backoffice.platform.municipal-context.index') }}" class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
                    <div>
                        <label for="municipality-search" class="block text-sm font-semibold text-ink-900">
                            Nome, código ou NIF institucional
                        </label>
                        <input
                            id="municipality-search"
                            name="q"
                            type="search"
                            value="{{ $search }}"
                            maxlength="100"
                            autocomplete="off"
                            class="mt-2 w-full rounded-2xl border-ink-200 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                        >
                    </div>

                    <div>
                        <label for="municipality-status" class="block text-sm font-semibold text-ink-900">
                            Estado
                        </label>
                        <select
                            id="municipality-status"
                            name="status"
                            class="mt-2 w-full rounded-2xl border-ink-200 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                        >
                            <option value="all" @selected($status === 'all')>Todos</option>
                            <option value="active" @selected($status === 'active')>Ativos</option>
                            <option value="inactive" @selected($status === 'inactive')>Inativos</option>
                        </select>
                    </div>

                    <button type="submit" class="mv-button-secondary">
                        Aplicar filtros
                    </button>
                </form>
            </section>

            <section class="mv-surface overflow-hidden" aria-labelledby="municipality-list-title">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h2 id="municipality-list-title" class="text-lg font-semibold text-ink-950">
                        Municípios autorizados
                    </h2>
                    <p class="mt-1 text-sm text-ink-600">
                        A lista é paginada. Municípios inativos são apresentados apenas para consulta e não podem ser selecionados.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="mv-table min-w-full">
                        <caption class="sr-only">
                            Lista paginada de Municípios disponíveis para seleção de contexto operacional.
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Município</th>
                                <th scope="col">Identificação institucional</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($municipalities as $municipality)
                                @php
                                    $isCurrent = $currentMunicipality
                                        && $currentMunicipality->is($municipality);
                                @endphp
                                <tr>
                                    <td>
                                        <p class="font-semibold text-ink-900">
                                            {{ $municipality->name }}
                                        </p>
                                        <p class="mt-1 text-xs text-ink-500">
                                            Código: {{ $municipality->code }}
                                        </p>
                                    </td>
                                    <td class="text-sm text-ink-700">
                                        {{ $municipality->tax_number ?: 'NIF não registado' }}
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <x-mv.badge :tone="$municipality->active ? 'success' : 'neutral'">
                                                {{ $municipality->active ? 'Ativo' : 'Inativo' }}
                                            </x-mv.badge>

                                            @if ($isCurrent)
                                                <x-mv.badge tone="info">Contexto atual</x-mv.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        @if ($isCurrent)
                                            <span class="text-sm font-semibold text-ink-500">
                                                Selecionado
                                            </span>
                                        @elseif (! $municipality->active)
                                            <span class="text-sm font-semibold text-ink-400" aria-disabled="true">
                                                Indisponível
                                            </span>
                                        @else
                                            <details
                                                class="inline-block max-w-md text-left"
                                                @if ((int) old('municipality_id') === (int) $municipality->id) open @endif
                                            >
                                                <summary class="mv-button-secondary cursor-pointer list-none">
                                                    Selecionar
                                                </summary>

                                                <form
                                                    method="POST"
                                                    action="{{ route('backoffice.platform.municipal-context.store') }}"
                                                    class="mt-3 rounded-3xl border border-ink-100 bg-white p-4 shadow-surface"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="municipality_id" value="{{ $municipality->id }}">

                                                    <label
                                                        for="municipality-justification-{{ $municipality->id }}"
                                                        class="block text-sm font-semibold text-ink-900"
                                                    >
                                                        Justificação operacional
                                                    </label>
                                                    <textarea
                                                        id="municipality-justification-{{ $municipality->id }}"
                                                        name="justification"
                                                        rows="3"
                                                        required
                                                        minlength="10"
                                                        maxlength="500"
                                                        class="mt-2 w-full rounded-2xl border-ink-200 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                                                    >{{ (int) old('municipality_id') === (int) $municipality->id ? old('justification') : '' }}</textarea>

                                                    <label class="mt-3 flex items-start gap-3 text-sm text-ink-700">
                                                        <input
                                                            type="checkbox"
                                                            name="confirm"
                                                            value="1"
                                                            required
                                                            class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary"
                                                        >
                                                        <span>
                                                            Confirmo a entrada no contexto de {{ $municipality->name }}.
                                                        </span>
                                                    </label>

                                                    <button type="submit" class="mv-button-secondary mt-4">
                                                        Confirmar contexto
                                                    </button>
                                                </form>
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-ink-500">
                                        Não foram encontrados Municípios com os filtros indicados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 p-4">
                    {{ $municipalities->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
