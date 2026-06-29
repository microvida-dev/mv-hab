@props([
    'for' => null,
    'label' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <x-ui.label :for="$for">
            {{ $label }}

            @if ($required)
                <span class="text-red-600">*</span>
            @endif
        </x-ui.label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs leading-5 text-ink-500">
            {{ $hint }}
        </p>
    @endif

    @if ($name)
        <x-ui.field-error :name="$name" />
    @endif
</div>
