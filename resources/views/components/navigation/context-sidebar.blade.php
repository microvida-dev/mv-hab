@props([
    'groups' => [],
    'responsive' => false,
])

@php
    $normalizedGroups = [];

    foreach ($groups as $key => $group) {
        $normalizedGroups[] = is_array($group) && array_key_exists('items', $group)
            ? ['label' => $group['label'] ?? 'Navegação', 'items' => $group['items'] ?? []]
            : ['label' => is_string($key) ? $key : 'Navegação', 'items' => $group];
    }
@endphp

@foreach ($normalizedGroups as $group)
    <div class="{{ $loop->first ? '' : 'mt-7' }}">
        <p
            class="px-3 text-xs font-semibold uppercase text-ink-500"
            x-show="!collapsed"
            x-transition.opacity
        >
            {{ $group['label'] }}
        </p>

        <div class="mt-2 space-y-1">
            @foreach ($group['items'] as $link)
                @continue(! is_array($link))
                @continue(! isset($link['route']) || ! \Illuminate\Support\Facades\Route::has($link['route']))

                @php
                    $parameters = $link['parameters'] ?? [];
                    $isActive = request()->routeIs($link['active'] ?? $link['route']);
                    $iconName = $link['icon'] ?? 'dashboard';
                @endphp

                @if ($responsive)
                    <x-responsive-nav-link :href="route($link['route'], $parameters)" :active="$isActive">
                        <x-mv-icon :name="$iconName" size="lg" class="shrink-0" />
                        <span>{{ $link['label'] }}</span>
                    </x-responsive-nav-link>
                @else
                    <a
                        href="{{ route($link['route'], $parameters) }}"
                        title="{{ $link['label'] }}"
                        @class([
                            'flex items-center rounded-2xl text-sm font-semibold transition duration-200',
                            'gap-3 px-3 py-2',
                            'bg-mvhab-surface text-mvhab-primary' => $isActive,
                            'text-ink-600 hover:bg-mvhab-surface hover:text-ink-900' => ! $isActive,
                        ])
                        x-bind:class="collapsed ? 'justify-center px-3 py-3' : 'gap-3 px-3 py-2'"
                    >
                        <x-mv-icon :name="$iconName" size="lg" class="shrink-0" />

                        <span
                            class="truncate"
                            x-show="!collapsed"
                            x-transition.opacity
                        >
                            {{ $link['label'] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endforeach
