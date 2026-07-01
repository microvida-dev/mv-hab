@php
    $svg = $svg();

    if ($svg !== '') {
        $svg = preg_replace('/\s(width|height)=["\'][^"\']*["\']/i', '', $svg);
        $svg = preg_replace('/\sclass=["\'][^"\']*["\']/i', '', $svg, 1);

        $svg = preg_replace(
            '/<svg\b/',
            '<svg class="block h-full w-full" preserveAspectRatio="xMidYMid meet"',
            $svg,
            1
        );

        $svg = preg_replace(
            '/<svg\b([^>]*)>/',
            '<svg$1 aria-hidden="true" focusable="false">',
            $svg,
            1
        );
    }
@endphp

<span {{ $attributes->merge(['class' => $classes().' inline-flex shrink-0 items-center justify-center overflow-hidden [&>svg]:h-full [&>svg]:w-full [&>svg]:max-h-full [&>svg]:max-w-full']) }}>
    {!! $svg !!}
</span>
