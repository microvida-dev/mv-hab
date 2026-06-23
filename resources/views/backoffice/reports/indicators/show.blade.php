<x-app-layout>
    <x-slot name="header"><div><h1 class="text-xl font-semibold text-ink-900">{{ $indicator->name }}</h1><p class="mt-1 text-sm text-ink-500">{{ $indicator->description }}</p></div></x-slot>
    <div class="max-w-2xl">
        <div class="mv-card"><p class="text-sm text-ink-500">{{ $indicator->category->label() }}</p><p class="mt-3 text-4xl font-semibold text-ink-900">{{ is_array($result['value']) ? json_encode($result['value'], JSON_UNESCAPED_UNICODE) : ($result['value'] ?? 'Indisponível') }}</p><p class="mt-3 text-xs text-ink-400">Calculado em {{ $result['calculated_at']->format('d/m/Y H:i:s') }}</p></div>
    </div>
</x-app-layout>
