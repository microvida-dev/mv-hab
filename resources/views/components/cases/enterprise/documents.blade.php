@props([
    'documents' => [],
])

<section id="case-tab-documents" class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Documentos e anexos" description="Documentos privados continuam protegidos por rotas e policies próprias." />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($documents as $document)
            <article class="px-5 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ $document['label'] }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $document['description'] }}</p>
                    </div>
                    <x-ui.status-badge :status="$document['status']" />
                </div>

                @if ($document['route'] && $document['route_parameter'])
                    <a href="{{ route($document['route'], $document['route_parameter']) }}" class="mt-3 inline-flex text-sm font-semibold text-mvhab-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2">
                        Abrir documento
                    </a>
                @endif
            </article>
        @empty
            <div class="p-5">
                <x-cases.enterprise.empty-state title="Sem documentos visíveis" description="Não existem documentos autorizados para apresentar neste caso." />
            </div>
        @endforelse
    </div>
</section>
