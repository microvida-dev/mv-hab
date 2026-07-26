@props([
    'favorites' => [],
])

@php
    $favoriteItems = collect($favorites)
        ->filter(fn ($favorite) => $favorite->route_name && \Illuminate\Support\Facades\Route::has($favorite->route_name))
        ->map(fn ($favorite) => [
            'id' => $favorite->id,
            'label' => $favorite->label,
            'url' => route($favorite->route_name, $favorite->route_parameters ?? []),
            'destroy_url' => route('navigation.favorites.destroy', $favorite),
        ])
        ->values()
        ->all();
@endphp

<section
    class="mv-card"
    x-data="{
        items: @js($favoriteItems),
        dragging: null,
        saving: false,

        startDrag(index) {
            this.dragging = index;
        },

        moveItem(targetIndex) {
            if (this.dragging === null || this.dragging === targetIndex) {
                return;
            }

            const item = this.items.splice(this.dragging, 1)[0];
            this.items.splice(targetIndex, 0, item);
            this.dragging = targetIndex;
        },

        async persistOrder() {
            if (this.items.length === 0) {
                return;
            }

            this.saving = true;

            try {
                await fetch('{{ route('navigation.favorites.reorder') }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        favorites: this.items.map((item) => item.id),
                    }),
                });
            } finally {
                this.saving = false;
                this.dragging = null;
            }
        },

        async remove(itemId) {
            const item = this.items.find((entry) => entry.id === itemId);

            if (! item || ! confirm('Remover este favorito?')) {
                return;
            }

            await fetch(item.destroy_url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                },
            });

            this.items = this.items.filter((entry) => entry.id !== itemId);
        },
    }"
>
    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Favoritos" />

        <div class="flex items-center gap-2">
            <span
                x-show="saving"
                x-cloak
                class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary"
            >
                A guardar
            </span>

            <template x-if="items.length > 0">
                <span class="text-xs font-semibold uppercase tracking-wide text-ink-400" x-text="items.length"></span>
            </template>
        </div>
    </div>

    <div class="divide-y divide-ink-100">
        <template x-for="(item, index) in items" :key="item.id">
            <div
                draggable="true"
                @dragstart="startDrag(index)"
                @dragenter.prevent="moveItem(index)"
                @dragover.prevent
                @dragend="persistOrder()"
                class="group flex cursor-move items-center gap-3 px-5 py-4 transition hover:bg-ink-50"
                x-bind:class="dragging === index ? 'bg-mvhab-surface opacity-80' : ''"
            >
                <span class="text-xs font-semibold text-ink-300" aria-hidden="true">
                    ⋮⋮
                </span>

                <a
                    x-bind:href="item.url"
                    class="flex min-w-0 flex-1 items-center gap-3 text-sm font-medium text-ink-700 transition hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="check" size="xs" />
                    </span>

                    <span class="truncate" x-text="item.label"></span>
                </a>

                <button
                    type="button"
                    class="rounded-xl px-2 py-1 text-xs font-semibold text-ink-400 opacity-100 transition hover:bg-red-50 hover:text-red-700 md:opacity-0 md:group-hover:opacity-100"
                    @click="remove(item.id)"
                    x-bind:aria-label="`Remover favorito ${item.label}`"
                >
                    Remover
                </button>
            </div>
        </template>

        <template x-if="items.length === 0">
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem favoritos"
                    description="Fixe os espaços de trabalho usados com frequência para acesso rápido."
                    icon="check"
                />
            </div>
        </template>
    </div>
</section>
