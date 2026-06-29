@props([
    'name',
    'value' => null,
])

<textarea
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->merge(['class' => 'mv-textarea']) }}
>{{ old($name, $value) }}</textarea>
