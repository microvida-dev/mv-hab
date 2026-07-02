@props([
    'workspace',
    'isFavorite' => false,
])

@php
    $moduleCount = collect($workspace['groups'] ?? [])
        ->flatMap(fn ($group) => $group['items'] ?? [])
        ->count();
@endphp

<article class="mv-card-interactive group flex h-full flex-col justify-between p-5">
    <div>
        <div class="flex items-start justify-between gap-4">
            <span class="flex h-14 w-14 items-center justify-center rounded-3xl bg-mvhab-surface text-mvhab-primary transition group-hover:scale-105">
                <x-mv-icon :name="$workspace['icon'] ?? 'dashboard'" size="lg" />
            </span>

            <form method="POST" action="{{ route('navigation.favorites.store') }}">
                @csrf
                <input type="hidden" name="workspace_key" value="{{ $workspace['key'] }}">
                <button
                    type="submit"
                    class="rounded-2xl border border-ink-100 px-2.5 py-2 text-xs font-semibold text-ink-600 transition hover:border-mvhab-support hover:bg-mvhab-surface hover:text-mvhab-primaryLight focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2"
                    aria-label="Fixar {{ $workspace['title'] }}"
                    title="Fixar espaço de trabalho"
                >
                    {{ $isFavorite ? 'Fixado' : 'Fixar' }}
                </button>
            </form>
        </div>

        <a href="{{ route('workspaces.show', $workspace['key']) }}" class="mt-5 block rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2">
            <span class="block text-lg font-semibold text-ink-950">{{ $workspace['title'] }}</span>
            <span class="mt-2 block text-sm leading-6 text-ink-600">{{ $workspace['description'] }}</span>
        </a>
    </div>

    <div class="mt-6 flex items-center justify-between border-t border-ink-100 pt-4">
        <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">
            {{ $moduleCount }} {{ $moduleCount === 1 ? 'módulo' : 'módulos' }}
        </span>

        <a href="{{ route('workspaces.show', $workspace['key']) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-mvhab-primary transition hover:text-mvhab-primaryLight">
            <span>Entrar</span>
            <x-mv-icon name="arrow-right" size="sm" />
        </a>
    </div>
</article>
