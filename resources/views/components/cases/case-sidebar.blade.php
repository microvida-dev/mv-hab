@props([
    'workspace',
])

@php
    $summary = $workspace['summary'];
    $sidebar = $workspace['sidebar'];
    $nextAction = $workspace['next_action'];
@endphp

<section class="rounded-md border border-ink-100 bg-white">
    <div class="border-b border-ink-100 px-5 py-4">
        <h2 class="text-base font-semibold text-ink-900">Painel do processo</h2>
    </div>
    <div class="space-y-4 p-5 text-sm">
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Estado atual</p>
            <p class="mt-1 font-semibold text-ink-900">{{ $summary['status'] }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">SLA</p>
            <p class="mt-1 text-ink-700">{{ $summary['sla'] }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Tarefas abertas</p>
            <p class="mt-1 text-ink-700">{{ $sidebar['open_tasks'] }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Alertas</p>
            <p class="mt-1 text-ink-700">{{ $sidebar['alerts'] }}</p>
        </div>

        <x-cases.next-action :action="$nextAction" />

        @if ($sidebar['quick_links'] !== [])
            <div class="border-t border-ink-100 pt-4">
                <p class="text-xs font-semibold uppercase text-ink-500">Links rápidos</p>
                <div class="mt-2 space-y-2">
                    @foreach ($sidebar['quick_links'] as $link)
                        <a href="{{ route($link['route'], $link['parameters'] ?? []) }}" class="block rounded-md bg-ink-50 px-3 py-2 font-semibold text-civic-700">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
