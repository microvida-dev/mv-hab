@props([
    'name',
    'label',
    'checked' => false,
    'align' => 'center',
])

<label @class([
    'flex gap-3 rounded-2xl border border-ink-100 px-4 py-3 text-sm text-ink-700 transition hover:border-mvhab-primary/30 hover:bg-mvhab-surface',
    'items-center' => $align === 'center',
    'items-start' => $align === 'start',
])>
    <input
        type="checkbox"
        name="{{ $name }}"
        value="1"
        class="mv-checkbox {{ $align === 'start' ? 'mt-0.5' : '' }}"
        @checked($checked)
    >

    <span>{{ $label }}</span>
</label>
