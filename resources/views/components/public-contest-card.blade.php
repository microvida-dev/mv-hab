@props(['contest'])

@php
    $phase = $contest->publicPhase();
    $phaseLabel = match ($phase) {
        'open' => 'Candidaturas abertas',
        'upcoming' => 'Abertura futura',
        'closed' => 'Prazo encerrado',
        'cancelled' => 'Cancelado',
        default => 'Publicado',
    };
    $phaseClasses = match ($phase) {
        'open' => 'bg-mvhab-surface text-mvhab-primary',
        'upcoming' => 'bg-sky-50 text-sky-800',
        'closed' => 'bg-ink-100 text-ink-700',
        'cancelled' => 'bg-red-50 text-red-800',
        default => 'bg-ink-100 text-ink-700',
    };
@endphp

<article class="mv-surface flex h-full flex-col p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="rounded-2xl px-2.5 py-1 text-xs font-semibold {{ $phaseClasses }}">{{ $phaseLabel }}</span>
        <span class="text-xs font-semibold text-ink-500">{{ $contest->code }}</span>
    </div>

    <h3 class="mt-4 text-lg font-semibold text-ink-900">
        <a href="{{ route('public.contests.show', $contest->slug) }}" class="hover:text-mvhab-primary">{{ $contest->title }}</a>
    </h3>
    <p class="mt-2 text-sm leading-6 text-ink-500">{{ $contest->summary }}</p>

    <div class="mt-5 border-t border-ink-100 pt-4 text-sm text-ink-600">
        <p><span class="font-semibold text-ink-900">Programa:</span> {{ $contest->program->name }}</p>
        <p class="mt-1"><span class="font-semibold text-ink-900">Município:</span> {{ $contest->program->municipality->name }}</p>
        <p class="mt-1"><span class="font-semibold text-ink-900">Prazo:</span> {{ $contest->opens_at->format('d/m/Y') }} a {{ $contest->closes_at->format('d/m/Y') }}</p>
    </div>

    <a href="{{ route('public.contests.show', $contest->slug) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">
        Consultar concurso
        <x-ui-icon name="arrow" class="h-4 w-4" />
    </a>
</article>
