@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
    'padding' => 'p-6',
])

<section {{ $attributes->merge([
    'class' => "mv-surface {$padding}",
]) }}>
    @if ($eyebrow || $title || $description)
        <div class="mb-5">
            @if ($eyebrow)
                <p class="text-sm font-semibold text-mvhab-primary">
                    {{ $eyebrow }}
                </p>
            @endif

            @if ($title)
                <h2 @class([
                    'text-lg font-semibold text-ink-900',
                    'mt-1' => $eyebrow,
                ])>
                    {{ $title }}
                </h2>
            @endif

            @if ($description)
                <p class="mt-2 text-sm leading-6 text-ink-500">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
