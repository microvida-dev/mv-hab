@props([
    'productivity' => [],
])

@if (($productivity['enabled'] ?? false) === true)
    <x-productivity.action-center
        :sections="$productivity['action_center'] ?? []"
        compact
    />
@endif
