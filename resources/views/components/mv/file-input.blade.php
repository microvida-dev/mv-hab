@props([
    'id',
    'name',
    'required' => false,
    'accept' => null,
    'multiple' => false,
])

<input
    id="{{ $id }}"
    name="{{ $name }}"
    type="file"
    @required($required)
    @if($accept) accept="{{ $accept }}" @endif
    @if($multiple) multiple @endif
    {{ $attributes->merge([
        'class' => 'mt-1 block w-full cursor-pointer rounded-2xl border border-ink-200 px-3 py-2 text-sm text-ink-700 transition hover:border-mvhab-primary/40 hover:bg-mvhab-surface focus:border-mvhab-primary focus:outline-none focus:ring-2 focus:ring-mvhab-primary/20 file:mr-4 file:cursor-pointer file:rounded-2xl file:border-0 file:bg-mvhab-surface file:px-4 file:py-2 file:text-sm file:font-semibold file:text-mvhab-primary file:transition hover:file:bg-ink-100',
    ]) }}
>
