@props([
    'tabs' => [],
])

<nav class="overflow-x-auto rounded-md border border-ink-100 bg-white" aria-label="Separadores do processo">
    <div class="flex min-w-max gap-1 p-2">
        @foreach ($tabs as $tab)
            <a href="#case-tab-{{ $tab['key'] }}" class="rounded-md px-3 py-2 text-sm font-semibold text-ink-600 transition hover:bg-ink-50 hover:text-ink-900">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
