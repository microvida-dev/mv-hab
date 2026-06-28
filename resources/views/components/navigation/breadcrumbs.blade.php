@php
    $route = request()->route();
    $routeName = $route?->getName();
    $workspaceParam = $route?->parameter('workspace');
    $workspaceKey = is_string($workspaceParam) ? $workspaceParam : null;
    $breadcrumbs = app(\App\Services\Navigation\BreadcrumbService::class)
        ->forRoute(Auth::user(), $routeName, $workspaceKey);
@endphp

@if (count($breadcrumbs) > 1)
    <nav class="border-b border-ink-100 bg-white/90 shadow-surface" aria-label="Breadcrumb">
        <ol class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-3 text-sm sm:px-6 lg:px-8">
            @foreach ($breadcrumbs as $breadcrumb)
                <li class="flex items-center gap-2">
                    @if (! $loop->first)
                        <span class="text-ink-300">/</span>
                    @endif

                    @if ($breadcrumb['route'] !== null && ! $loop->last)
                        <a href="{{ route($breadcrumb['route'], $breadcrumb['parameters']) }}" class="rounded-sm font-medium text-ink-500 transition hover:text-civic-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-offset-2">
                            {{ $breadcrumb['label'] }}
                        </a>
                    @else
                        <span class="font-semibold text-ink-900">{{ $breadcrumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
