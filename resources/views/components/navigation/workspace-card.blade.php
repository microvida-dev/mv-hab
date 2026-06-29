@props([
    'workspace',
    'isFavorite' => false,
])

<article class="mv-card-interactive p-5">
    <div class="flex items-start justify-between gap-4">
        <a href="{{ route('workspaces.show', $workspace['key']) }}" class="min-w-0 flex-1 rounded-2xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-mvhab-surface text-sm font-bold text-mvhab-primary">
                {{ $workspace['short_label'] ?? mb_substr((string) $workspace['title'], 0, 2) }}
            </span>
            <span class="mt-4 block text-base font-semibold text-ink-900">{{ $workspace['title'] }}</span>
            <span class="mt-2 block text-sm leading-5 text-ink-500">{{ $workspace['description'] }}</span>
        </a>

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
</article>
