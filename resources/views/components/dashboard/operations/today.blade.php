@props([
    'items' => [],
    'timeline' => [],
])

@php
    $nextAction = $timeline['nextAction'] ?? null;
    $groups = $timeline['groups'] ?? [];
    $fallbackItems = $items ?? [];
@endphp

<section class="mv-card p-5">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                Operação
            </p>

            <h2 class="mt-1 text-lg font-semibold text-ink-950">
                Hoje
            </h2>
        </div>

        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon name="calendar" size="md" />
        </span>
    </div>

    @if($nextAction)
        <div class="mt-5 rounded-3xl border border-mvhab-primary/20 bg-mvhab-primary/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                Próxima ação recomendada
            </p>

            <div class="mt-3 flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-mvhab-primary">
                    <x-mv-icon :name="$nextAction['icon'] ?? 'calendar'" size="sm" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink-950">
                        {{ $nextAction['title'] ?? 'Ação pendente' }}
                    </p>

                    @if(!empty($nextAction['description']))
                        <p class="mt-1 text-sm text-ink-600">
                            {{ $nextAction['description'] }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6 space-y-6">
        @forelse($groups as $group)
            <div>
                <div class="mb-3 flex items-center gap-3">
                    <span class="h-px flex-1 bg-ink-100"></span>
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        {{ $group['label'] ?? 'Hoje' }}
                    </span>
                    <span class="h-px flex-1 bg-ink-100"></span>
                </div>

                <div class="divide-y divide-ink-100">
                    @foreach(($group['items'] ?? []) as $alert)
                        <div class="flex items-start gap-4 py-4">
                            <div class="w-12 shrink-0 pt-1 text-xs font-semibold text-ink-500">
                                {{ $alert['time'] ?? '—' }}
                            </div>

                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-mvhab-surface text-mvhab-primary">
                                <x-mv-icon :name="$alert['icon'] ?? 'calendar'" size="sm" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-ink-900">
                                        {{ $alert['title'] ?? $alert['label'] ?? 'Prazo' }}
                                    </p>

                                    @if(!empty($alert['priority']))
                                        <span class="rounded-full bg-ink-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink-600">
                                            {{ $alert['priority'] }}
                                        </span>
                                    @endif
                                </div>

                                @if(!empty($alert['description']))
                                    <p class="mt-1 text-sm text-ink-600">
                                        {{ $alert['description'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="divide-y divide-ink-100">
                @forelse($fallbackItems as $alert)
                    <div class="flex items-start gap-4 py-4">
                        <span class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-mvhab-surface text-mvhab-primary">
                            <x-mv-icon :name="$alert['icon'] ?? 'calendar'" size="sm" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink-900">
                                {{ $alert['title'] ?? $alert['label'] ?? 'Prazo' }}
                            </p>

                            @if(!empty($alert['description']))
                                <p class="mt-1 text-sm text-ink-600">
                                    {{ $alert['description'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state
                        title="Sem atividades para hoje"
                        description="Não existem tarefas ou prazos autorizados."
                    />
                @endforelse
            </div>
        @endforelse
    </div>
</section>
