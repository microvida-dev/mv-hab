@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

<select
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->merge(['class' => 'mv-select']) }}
>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected(old($name, $selected) == $value)>
            {{ $label }}
        </option>
    @endforeach

    {{ $slot }}
</select>
