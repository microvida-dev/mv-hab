<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Contrato"
            :title="$leaseContract->contract_number"
            description="Contrato disponível para consulta após emissão pelos serviços municipais."
        >
            <x-slot name="actions">
                <a class="mv-button-secondary" href="{{ route('candidate.contracts.deposit.show', $leaseContract) }}">Ver caução</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section
                title="Dados do contrato"
                description="Os documentos apresentados nesta área correspondem à versão registada no sistema."
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <x-mv.stat-card label="Estado" :value="$leaseContract->status->label()" />
                    <x-mv.stat-card label="Renda" :value="$leaseContract->monthly_rent" />
                    <x-mv.stat-card label="Caução" :value="$leaseContract->deposit?->amount ?? '-'" />
                </div>

                <dl class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-ink-500">Habitação</dt>
                        <dd class="font-semibold text-ink-900">{{ $leaseContract->housing_address }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Início</dt>
                        <dd class="font-semibold text-ink-900">{{ $leaseContract->start_date?->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Fim</dt>
                        <dd class="font-semibold text-ink-900">{{ $leaseContract->end_date?->format('d/m/Y') }}</dd>
                    </div>
                </dl>
            </x-mv.section>

            <x-mv.section title="Documentos disponíveis">
                <ul class="space-y-3 text-sm">
                    @forelse ($leaseContract->generatedDocuments as $document)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-ink-100 p-3">
                            <a class="font-semibold text-mvhab-primary" href="{{ route('candidate.contracts.documents.download', $document) }}">
                                {{ $document->title }}
                            </a>
                            <x-mv.badge>{{ $document->status->label() }}</x-mv.badge>
                        </li>
                    @empty
                        <x-mv.alert>Sem documentos emitidos.</x-mv.alert>
                    @endforelse
                </ul>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
