@props([
    'name',
    'type' => 'text',
    'value' => null,
])

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    value="{{ old($name, $value) }}"
    {{ $attributes->merge(['class' => 'mv-input']) }}
>
