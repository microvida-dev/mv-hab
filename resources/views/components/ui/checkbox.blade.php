@props([
    'name',
    'value' => '1',
    'checked' => false,
])

<input
    type="checkbox"
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    value="{{ $value }}"
    @checked(old($name, $checked))
    {{ $attributes->merge(['class' => 'mv-checkbox']) }}
>
