@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => '',
    'icon' => 'user',
    'placeholder' => '',
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'readonly' => false,
])

<div>
    <x-input-label
        :for="$id"
        :value="$label"
        class="text-sm font-semibold text-slate-700"
    />

    <div
        class="mt-2 flex overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 focus-within:border-purple-500 focus-within:ring-4 focus-within:ring-purple-100"
    >
        <span
            class="flex w-12 shrink-0 items-center justify-center border-r border-slate-200 text-slate-400"
        >
            <x-mv-icon :name="$icon" class="h-5 w-5" />
        </span>

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @required($required)
            @autofocus($autofocus)
            @readonly($readonly)

            {{ $attributes->merge([
                'class' => 'block w-full border-0 bg-transparent px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0',
            ]) }}
        >
    </div>

    <x-input-error
        :messages="$errors->get($name)"
        class="mt-2 text-sm text-red-600"
    />
</div>
