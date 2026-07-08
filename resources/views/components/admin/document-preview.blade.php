@props([
    'submission',
])

@php
    $version = $submission->currentVersion;
    $mimeType = $version?->mime_type ?: $submission->mime_type;
    $previewUrl = route('admin.document-reviews.preview', $submission);
    $isPdf = $mimeType === 'application/pdf';
    $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
@endphp

<section
    class="mv-surface overflow-hidden"
    x-data="{
        zoom: 100,
        rotation: 0,
        zoomIn() { this.zoom = Math.min(this.zoom + 10, 200) },
        zoomOut() { this.zoom = Math.max(this.zoom - 10, 50) },
        resetZoom() { this.zoom = 100; this.rotation = 0 },
        rotate() { this.rotation = (this.rotation + 90) % 360 }
    }"
>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-4">
        <div>
            <h2 class="text-base font-semibold text-ink-900">Pré-visualização segura</h2>
            <p class="mt-1 text-sm text-ink-500">
                Consulte o documento dentro da plataforma, sem descarregar o ficheiro.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="mv-button-secondary" x-on:click="zoomOut">−</button>

            <span class="min-w-16 text-center text-sm font-semibold text-ink-700" x-text="zoom + '%'"></span>

            <button type="button" class="mv-button-secondary" x-on:click="zoomIn">+</button>
            <button type="button" class="mv-button-secondary" x-on:click="rotate">Rodar</button>
            <button type="button" class="mv-button-secondary" x-on:click="resetZoom">Repor</button>
        </div>
    </div>

    <div class="h-[72vh] overflow-auto bg-ink-950/5 p-4">
        @if ($isPdf)
            <div
                class="mx-auto origin-top transition-transform"
                x-bind:style="`width: ${zoom}%; transform: rotate(${rotation}deg);`"
            >
                <iframe
                    src="{{ $previewUrl }}#toolbar=0&navpanes=0&scrollbar=1"
                    class="h-[68vh] w-full rounded-2xl border border-ink-200 bg-white"
                    title="Pré-visualização do documento {{ $submission->original_filename }}"
                ></iframe>
            </div>
        @elseif ($isImage)
            <div class="flex min-h-full items-start justify-center">
                <img
                    src="{{ $previewUrl }}"
                    alt="Pré-visualização do documento {{ $submission->original_filename }}"
                    class="max-w-none origin-top rounded-2xl border border-ink-200 bg-white transition-transform"
                    x-bind:style="`width: ${zoom}%; transform: rotate(${rotation}deg);`"
                >
            </div>
        @else
            <div class="flex h-full items-center justify-center">
                <div class="max-w-md rounded-2xl bg-white p-6 text-center">
                    <h3 class="text-base font-semibold text-ink-900">Pré-visualização indisponível</h3>
                    <p class="mt-2 text-sm leading-6 text-ink-600">
                        Este tipo de ficheiro não pode ser pré-visualizado de forma segura nesta fase.
                        Use o download seguro apenas quando necessário.
                    </p>
                </div>
            </div>
        @endif
    </div>
</section>
