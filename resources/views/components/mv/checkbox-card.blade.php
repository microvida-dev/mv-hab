@props([
    'name',
    'label',
    'checked' => false,
    'align' => 'center',
    'tone' => 'default',
])

<label @class([
    'flex gap-3 rounded-2xl border px-4 py-3 text-sm transition',
    'items-center' => $align === 'center',
    'items-start' => $align === 'start',
    'border-ink-100 text-ink-700 hover:border-mvhab-primary/30 hover:bg-mvhab-surface' => $tone === 'default',
    'border-red-200 text-red-800 hover:bg-red-100' => $tone === 'danger',
])>
    <input {{ $attributes }}
        type="checkbox"
        name="{{ $name }}"
        value="1"
        @class([
            'mv-checkbox',
            'mt-0.5' => $align === 'start',
            'border-red-300 text-red-700 focus:ring-red-500' => $tone === 'danger',
        ])
        @checked($checked)
    >

    <span>{{ $label }}</span>
</label>
