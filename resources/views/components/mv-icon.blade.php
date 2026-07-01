@php
    $svg = $svg();

    if ($svg !== '') {
        $svg = preg_replace(
            '/<svg\b([^>]*)class="[^"]*"/i',
            '<svg$1',
            $svg
        );

        $svg = preg_replace(
            '/<svg\b/',
            '<svg class="'.$classes().' '.$attributes->get('class').'"',
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

{!! $svg !!}
