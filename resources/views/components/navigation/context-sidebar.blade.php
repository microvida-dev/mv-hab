@props([
    'groups' => [],
    'responsive' => false,
])

@php
    $normalizedGroups = [];

    foreach ($groups as $key => $group) {
        if (is_array($group) && array_key_exists('items', $group)) {
            $normalizedGroups[] = [
                'label' => $group['label'] ?? 'Navegação',
                'items' => $group['items'] ?? [],
            ];
        } else {
            $normalizedGroups[] = [
                'label' => is_string($key) ? $key : 'Navegação',
                'items' => $group,
            ];
        }
    }
@endphp

@foreach ($normalizedGroups as $group)
    <div class="{{ $loop->first ? '' : 'mt-7' }}">
        <p class="px-3 text-xs font-semibold uppercase text-ink-500">{{ $group['label'] }}</p>
        <div class="mt-2 space-y-1">
            @foreach ($group['items'] as $link)
                @continue(! is_array($link))
                @continue(! isset($link['route']) || ! \Illuminate\Support\Facades\Route::has($link['route']))

                @php
                    $parameters = $link['parameters'] ?? [];
                    $isActive = request()->routeIs($link['active'] ?? $link['route']);
                @endphp

                @if ($responsive)
                    <x-responsive-nav-link :href="route($link['route'], $parameters)" :active="$isActive">
                        <x-ui-icon :name="$link['icon'] ?? 'dashboard'" class="h-4 w-4 shrink-0" />
                        <span>{{ $link['label'] }}</span>
                    </x-responsive-nav-link>
                @else
                    <x-nav-link :href="route($link['route'], $parameters)" :active="$isActive">
                        <x-ui-icon :name="$link['icon'] ?? 'dashboard'" class="h-4 w-4 shrink-0" />
                        <span>{{ $link['label'] }}</span>
                    </x-nav-link>
                @endif
            @endforeach
        </div>
    </div>
@endforeach
