@php
    $selected = collect($selectedPermissionIds)->map(fn ($id) => (int) $id)->unique()->values();
    $initialSelection = $selected->all();
    $matrixId = 'permission-matrix-'.str()->random(8);
@endphp

<x-mv.section
    title="Matriz de permissões"
    description="As permissões estão agrupadas por domínio municipal. O código técnico é apresentado apenas como referência secundária."
    padding="p-5"
>
    <div
        id="{{ $matrixId }}"
        class="space-y-5"
        x-data="{
            query: '',
            selected: @js($initialSelection),
            initial: @js($initialSelection),
            open: @js(collect($permissionGroups)->mapWithKeys(fn ($group) => [$group['key'] => true])->all()),
            addDomain(ids) {
                this.selected = [...new Set([...this.selected, ...ids])];
            },
            removeDomain(ids) {
                this.selected = this.selected.filter(id => !ids.includes(id));
            },
            changedCount() {
                const initial = new Set(this.initial);
                const current = new Set(this.selected);
                return [...current].filter(id => !initial.has(id)).length
                    + [...initial].filter(id => !current.has(id)).length;
            },
        }"
    >
        <div class="grid gap-4 rounded-2xl bg-ink-50 p-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
            <label class="grid gap-1 text-sm" for="{{ $matrixId }}-search">
                <span class="font-medium text-ink-700">Pesquisar permissões</span>
                <input
                    id="{{ $matrixId }}-search"
                    type="search"
                    x-model.debounce.150ms="query"
                    class="mv-input"
                    placeholder="Ex.: candidaturas, consultar ou applications.view"
                >
            </label>
            <div class="flex flex-wrap gap-2 text-sm" aria-live="polite">
                <x-mv.badge><span x-text="selected.length"></span>&nbsp;selecionadas</x-mv.badge>
                @unless ($readOnly)
                    <x-mv.badge tone="warning"><span x-text="changedCount()"></span>&nbsp;alterações</x-mv.badge>
                @endunless
            </div>
        </div>

        @forelse ($permissionGroups as $group)
            @php
                $domainIds = collect($group['permissions'])->pluck('id')->all();
                $domainSearch = str($group['label'].' '.collect($group['permissions'])->pluck('label')->join(' ').' '.collect($group['permissions'])->pluck('name')->join(' '))->lower()->toString();
            @endphp
            <fieldset
                class="rounded-2xl border border-ink-100 p-4"
                x-show="query === '' || @js($domainSearch).includes(query.toLowerCase())"
            >
                <legend class="w-full px-2">
                    <span class="flex flex-wrap items-center justify-between gap-3">
                        <button
                            type="button"
                            class="rounded-lg text-left text-sm font-semibold text-ink-900 focus:outline-none focus:ring-2 focus:ring-mvhab-primary focus:ring-offset-2"
                            @click="open[@js($group['key'])] = !open[@js($group['key'])]"
                            :aria-expanded="open[@js($group['key'])]"
                        >
                            {{ $group['label'] }}
                            <span class="ml-1 text-xs font-normal text-ink-500">({{ count($group['permissions']) }})</span>
                        </button>
                        @unless ($readOnly)
                            <span class="flex flex-wrap gap-2">
                                <button type="button" class="text-xs font-semibold text-mvhab-primary focus:outline-none focus:ring-2 focus:ring-mvhab-primary" @click="addDomain(@js($domainIds))">
                                    Selecionar domínio
                                </button>
                                <button type="button" class="text-xs font-semibold text-ink-600 focus:outline-none focus:ring-2 focus:ring-mvhab-primary" @click="removeDomain(@js($domainIds))">
                                    Limpar domínio
                                </button>
                            </span>
                        @endunless
                    </span>
                </legend>

                <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3" x-show="open[@js($group['key'])]">
                    @foreach ($group['permissions'] as $permission)
                        @php
                            $permissionSearch = str($permission['label'].' '.$permission['name'].' '.$permission['action_label'])->lower()->toString();
                        @endphp
                        <label
                            class="flex items-start gap-3 rounded-2xl border border-ink-100 p-3 text-sm has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-mvhab-primary"
                            x-show="query === '' || @js($permissionSearch).includes(query.toLowerCase())"
                        >
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission['id'] }}"
                                class="mv-checkbox mt-0.5"
                                x-model.number="selected"
                                @checked($selected->contains($permission['id']))
                                @disabled($readOnly)
                            >
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-ink-900">{{ $permission['label'] }}</span>
                                    @if ($permission['sensitive'])
                                        <x-mv.badge tone="warning">Sensível</x-mv.badge>
                                    @endif
                                </span>
                                <span class="mt-1 block text-xs text-ink-500">Ação: {{ $permission['action_label'] }}</span>
                                <code class="mt-1 block break-all text-xs text-ink-500">{{ $permission['name'] }}</code>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @empty
            <p class="rounded-2xl bg-ink-50 p-4 text-sm text-ink-500">Este perfil não tem permissões configuradas.</p>
        @endforelse
    </div>
    @unless ($readOnly)
        <x-input-error class="mt-3" :messages="$errors->get('permissions')" />
    @endunless
</x-mv.section>
