@props([
    'dashboard' => [],
    'productivity' => [],
])

@php
    $metrics = collect($dashboard['metrics'] ?? [])->take(4);
    $notification = $productivity['notification_summary'] ?? null;
    $nextCase = $productivity['next_case'] ?? null;
@endphp

<section class="grid gap-4 xl:grid-cols-[2fr_1fr]">
    <div class="mv-card p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                    Resumo Operacional
                </p>

                <h2 class="mt-1 text-lg font-semibold text-ink-950">
                    Indicadores do perfil
                </h2>
            </div>

            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                <x-mv-icon name="dashboard" size="md" />
            </span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @forelse($metrics as $metric)
                <x-dashboard.kpi-card :metric="$metric" />
            @empty
                <x-ui.empty-state
                    title="Sem indicadores"
                    description="Não existem indicadores autorizados."
                />
            @endforelse
        </div>
    </div>

    <div class="space-y-4">
        @if($notification)
            <x-ui.card>
                <div class="flex gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="bell" size="sm" />
                    </span>

                    <div>
                        <h3 class="font-semibold text-ink-900">
                            {{ $notification['label'] }}
                        </h3>

                        <p class="mt-1 text-sm text-ink-600">
                            {{ $notification['description'] }}
                        </p>
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if($nextCase)
            <x-productivity.next-case :next-case="$nextCase" />
        @endif
    </div>
</section>
