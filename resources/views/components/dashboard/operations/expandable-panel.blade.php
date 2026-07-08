@props([
    'id',
    'eyebrow' => null,
    'title',
    'description' => null,
    'icon' => null,
    'summary' => [],
    'defaultOpen' => true,
])

<section
    x-data="{
        open: JSON.parse(localStorage.getItem('mvhab-dashboard-panel-{{ $id }}') ?? '{{ $defaultOpen ? 'true' : 'false' }}'),
        toggle() {
            this.open = !this.open;
            localStorage.setItem('mvhab-dashboard-panel-{{ $id }}', JSON.stringify(this.open));
        }
    }"
    class="mv-card overflow-hidden"
>
    <button
        type="button"
        class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left transition hover:bg-mvhab-surface/60 focus:outline-none focus:ring-4 focus:ring-mvhab-primary/10"
        x-on:click="toggle()"
        x-bind:aria-expanded="open.toString()"
    >
        <span class="flex min-w-0 items-start gap-3">
            @if($icon)
                <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                    <x-mv-icon :name="$icon" size="md" />
                </span>
            @endif

            <span class="min-w-0">
                @if($eyebrow)
                    <span class="block text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                        {{ $eyebrow }}
                    </span>
                @endif

                <span class="block text-lg font-semibold text-ink-950">
                    {{ $title }}
                </span>

                <span x-show="open" x-cloak>
                    @if($description)
                        <span class="mt-1 block text-sm text-ink-500">
                            {{ $description }}
                        </span>
                    @endif
                </span>

                @if(!empty($summary))
                    <span class="mt-3 flex flex-wrap gap-2" x-show="!open" x-cloak>
                        @foreach($summary as $item)
                            <span class="rounded-full bg-ink-50 px-2.5 py-1 text-xs font-semibold text-ink-600">
                                {{ $item }}
                            </span>
                        @endforeach
                    </span>
                @endif
            </span>
        </span>

        <span
            class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-ink-500 ring-1 ring-ink-100 transition duration-200"
            x-bind:class="open ? 'rotate-180' : ''"
        >
            <x-mv-icon name="chevron" size="sm" />
        </span>
    </button>

    <div x-show="open" x-cloak>
        <div class="border-t border-ink-100">
            {{ $slot }}
        </div>
    </div>
</section>
