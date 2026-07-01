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
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden">
                            <x-mv-icon :name="$iconName" size="md" class="h-6 w-6" />
                        </span>
                        <span>{{ $link['label'] }}</span>
                    </x-responsive-nav-link>
                @else
                    <a
                        href="{{ route($link['route'], $parameters) }}"
                        title="{{ $link['label'] }}"
                        @class([
                            'group relative flex items-center rounded-2xl text-sm font-semibold transition duration-200',
                            'gap-3 px-3 py-2',
                            'bg-mvhab-surface text-mvhab-primary' => $isActive,
                            'text-ink-600 hover:bg-mvhab-surface hover:text-ink-900' => ! $isActive,
                        ])
                        x-bind:class="collapsed ? 'justify-center px-3 py-3' : 'gap-3 px-3 py-2'"
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden">
                            <x-mv-icon :name="$iconName" size="md" class="h-6 w-6" />
                        </span>

                        <span
                            class="truncate"
                            x-show="!collapsed"
                            x-transition.opacity
                        >
                            {{ $link['label'] }}
                        </span>

                        <span
                            x-show="collapsed"
                            x-cloak
                            class="pointer-events-none absolute left-full top-1/2 z-50 ml-3 -translate-y-1/2 whitespace-nowrap rounded-xl bg-ink-900 px-3 py-2 text-xs font-semibold text-white opacity-0 shadow-lg transition group-hover:opacity-100"
                        >
                            {{ $link['label'] }}
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endforeach
