@props([
    'widgets' => [],
    'favorites' => [],
    'recentItems' => [],
])

<div class="space-y-6">
    <x-dashboard.widget-panel :widgets="$widgets" />
    <x-navigation.favorites :favorites="$favorites" />
    <x-navigation.recent-items :items="$recentItems" />
</div>
