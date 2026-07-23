@php
    $selected = collect($selectedPermissionIds)->map(fn ($id) => (int) $id);
@endphp

<x-mv.section
    title="Matriz de permissões"
    description="Selecione apenas as capacidades necessárias à função municipal."
    padding="p-5"
>
    <div class="space-y-5">
        @foreach ($permissions->groupBy('module') as $module => $modulePermissions)
            <fieldset class="rounded-2xl border border-ink-100 p-4">
                <legend class="px-2 text-sm font-semibold text-ink-900">{{ str($module)->replace('_', ' ')->title() }}</legend>
                <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($modulePermissions as $permission)
                        <label class="flex items-start gap-3 rounded-2xl border border-ink-100 p-3 text-sm hover:border-mvhab-primary/30">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->id }}"
                                class="mv-checkbox mt-0.5"
                                @checked($selected->contains((int) $permission->id))
                            >
                            <span>
                                <span class="block font-medium text-ink-900">{{ str($permission->action)->replace('_', ' ')->title() }}</span>
                                <span class="block text-xs text-ink-500">{{ $permission->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
    <x-input-error class="mt-3" :messages="$errors->get('permissions')" />
</x-mv.section>
