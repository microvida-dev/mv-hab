@props([
    'action' => null,
])

@if($action)
    <div class="mt-5 rounded-3xl border border-mvhab-primary/20 bg-mvhab-primary/5 p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
            Próxima ação recomendada
        </p>

        <div class="mt-3 flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-mvhab-primary">
                <x-mv-icon :name="$action['icon'] ?? 'calendar'" size="sm" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="font-semibold text-ink-950">
                    {{ $action['title'] ?? 'Ação pendente' }}
                </p>

                @if(!empty($action['description']))
                    <p class="mt-1 text-sm text-ink-600">
                        {{ $action['description'] }}
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
