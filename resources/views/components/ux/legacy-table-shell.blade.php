<section {{ $attributes->merge(['class' => 'mv-card overflow-hidden']) }}>
    @isset($header)
        <div class="border-b border-ink-100 px-5 py-4">
            {{ $header }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        {{ $slot }}
    </div>
</section>
