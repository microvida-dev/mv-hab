@props([
    'type' => 'submit',
    'icon' => true,
])

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-700 to-purple-700 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-700/20 transition duration-200 hover:from-blue-800 hover:to-purple-800 focus:outline-none focus:ring-4 focus:ring-purple-200',
    ]) }}
>
    {{ $slot }}

    @if($icon)
        <span aria-hidden="true">→</span>
    @endif
</button>
