@props([
    'workspace',
    'featured' => false,
])

@php
    $title = data_get($workspace, 'title', 'Workspace');
    $description = data_get($workspace, 'description');
    $icon = data_get($workspace, 'icon', 'dashboard');
    $href = data_get($workspace, 'href');
    $isPreferred = (bool) data_get($workspace, 'is_preferred', false);
    $favoritesCount = (int) data_get($workspace, 'favorites_count', 0);
    $recentCount = (int) data_get($workspace, 'recent_count', 0);
    $modulesCount = (int) data_get($workspace, 'modules_count', 0);
@endphp

<article {{ $attributes->class([
    'rounded-3xl border border-ink-100 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-sm',
    'ring-2 ring-mvhab-primary/20' => $featured || $isPreferred,
]) }}>
    <div class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon :name="$icon" size="sm" />
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">
                        {{ $title }}
                    </h3>

                    @if ($description)
                        <p class="mt-1 text-xs leading-5 text-ink-500">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                @if ($isPreferred)
                    <span class="shrink-0 rounded-full bg-mvhab-primary/10 px-2.5 py-1 text-[11px] font-bold text-mvhab-primary">
                        Espaço inicial
                    </span>
                @endif
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-ink-50 px-2.5 py-1 text-[11px] font-semibold text-ink-600">
                    {{ $modulesCount }} módulo(s)
                </span>

                <span class="rounded-full bg-ink-50 px-2.5 py-1 text-[11px] font-semibold text-ink-600">
                    {{ $favoritesCount }} favorito(s)
                </span>

                <span class="rounded-full bg-ink-50 px-2.5 py-1 text-[11px] font-semibold text-ink-600">
                    {{ $recentCount }} recente(s)
                </span>
            </div>

            @if ($href)
                <div class="mt-4">
                    <a
                        href="{{ $href }}"
                        class="inline-flex items-center gap-2 text-xs font-bold text-mvhab-primary transition hover:text-mvhab-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary"
                    >
                        Entrar no workspace
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</article>
