<nav
    class="overflow-x-auto rounded-2xl border border-ink-100 bg-white p-2 shadow-sm"
    aria-label="Secções da candidatura"
>
    <div class="flex min-w-max gap-2">
        <a
            href="{{ route('candidate.applications.show', $application) }}"
            @class([
                'rounded-xl px-4 py-2 text-sm font-semibold transition',
                'bg-mvhab-primary text-white' => request()->routeIs('candidate.applications.show'),
                'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! request()->routeIs('candidate.applications.show'),
            ])
            @if (request()->routeIs('candidate.applications.show')) aria-current="page" @endif
        >
            Resumo
        </a>

        @if ($application->isEditable())
            <a
                href="{{ route('candidate.housing-preferences.edit', $application) }}"
                @class([
                    'rounded-xl px-4 py-2 text-sm font-semibold transition',
                    'bg-mvhab-primary text-white' => request()->routeIs('candidate.housing-preferences.*'),
                    'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! request()->routeIs('candidate.housing-preferences.*'),
                ])
                @if (request()->routeIs('candidate.housing-preferences.*')) aria-current="page" @endif
            >
                Fogos
            </a>

            <a
                href="{{ route('candidate.applications.review', $application) }}"
                @class([
                    'rounded-xl px-4 py-2 text-sm font-semibold transition',
                    'bg-mvhab-primary text-white' => request()->routeIs('candidate.applications.review'),
                    'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! request()->routeIs('candidate.applications.review'),
                ])
                @if (request()->routeIs('candidate.applications.review')) aria-current="page" @endif
            >
                Rever e submeter
            </a>
        @endif
    </div>
</nav>
