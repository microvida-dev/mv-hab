@props(['contest'])

@php
    $phase = $contest->publicPhase();

    $phaseLabel = match ($phase) {
        'open' => 'Aberto',
        'upcoming' => 'Brevemente',
        'closed' => 'Encerrado',
        'cancelled' => 'Cancelado',
        default => 'Publicado',
    };

    $phaseBadge = match ($phase) {
        'open' => 'mv-badge-success',
        'upcoming' => 'mv-badge-info',
        'closed' => 'mv-badge-neutral',
        'cancelled' => 'mv-badge-danger',
        default => 'mv-badge-neutral',
    };
@endphp

<article class="mv-card-interactive flex h-full flex-col overflow-hidden p-6">
    <div class="flex items-start justify-between gap-4">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon name="contest" size="lg" />
        </div>

        <div class="flex flex-col items-end gap-2">
            <span class="mv-badge {{ $phaseBadge }}">
                {{ $phaseLabel }}
            </span>

            <span class="text-xs font-semibold text-ink-400">
                {{ $contest->code }}
            </span>
        </div>
    </div>

    <div class="mt-6 flex-1">
        <h3 class="mv-card-title">
            <a href="{{ route('public.contests.show', $contest->slug) }}" class="hover:text-mvhab-primary">
                {{ $contest->title }}
            </a>
        </h3>

        <p class="mv-section-description mt-3">
            {{ $contest->summary }}
        </p>

        <div class="mt-6 grid gap-3 border-t border-ink-100 pt-5">
            <div class="flex items-start gap-3">
                <x-mv-icon name="program" size="sm" class="mt-0.5 text-mvhab-primary" />
                <div>
                    <p class="mv-data-label">Programa</p>
                    <p class="mv-data-value">{{ $contest->program->name }}</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <x-mv-icon name="location" size="sm" class="mt-0.5 text-mvhab-primary" />
                <div>
                    <p class="mv-data-label">Município</p>
                    <p class="mv-data-value">{{ $contest->program->municipality->name }}</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <x-mv-icon name="calendar" size="sm" class="mt-0.5 text-mvhab-primary" />
                <div>
                    <p class="mv-data-label">Prazo</p>
                    <p class="mv-data-value">
                        {{ $contest->opens_at->format('d/m/Y') }}
                        —
                        {{ $contest->closes_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <a
        href="{{ route('public.contests.show', $contest->slug) }}"
        class="mv-button-secondary mt-6 justify-center"
    >
        Consultar concurso
        <x-mv-icon name="arrow-right" size="sm" />
    </a>
</article>
