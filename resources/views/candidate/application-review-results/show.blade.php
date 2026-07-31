<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Resultado oficial</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Revisão documental</h1>
            <p class="mt-1 text-sm text-ink-500">Processo {{ $result->process_number }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-3">
                <x-mv.stat-card label="Resultado" :value="$result->outcome->label()" />
                <x-mv.stat-card label="Ciclo" :value="$result->result_payload['cycle_label']" />
                <x-mv.stat-card label="Publicado em" :value="$result->published_at->format('d/m/Y H:i')" />
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Informação publicada</h2>
                <p class="mt-4 text-sm leading-7 text-ink-700">{{ $result->result_payload['message'] }}</p>
            </section>
            @if ($result->outcome->value === 'correction_required')
                <x-mv.alert tone="warning" title="Aguarde o pedido formal de aperfeiçoamento">
                    Este resultado não substitui o pedido formal, a identificação dos elementos a corrigir nem o respetivo prazo. Essa informação será disponibilizada separadamente.
                </x-mv.alert>
            @elseif ($result->outcome->value === 'complete_pending_decision')
                <x-mv.alert tone="info" title="A revisão documental não é uma decisão final">
                    A conclusão sem bloqueios documentais não corresponde a admissão, elegibilidade, classificação, atribuição ou decisão final do procedimento.
                </x-mv.alert>
            @endif
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('candidate.application-review-results.index') }}" class="mv-button-secondary">Voltar aos resultados</a>
                <a href="{{ route('candidate.notifications.index') }}" class="mv-button-secondary">Notificações</a>
            </div>
        </div>
    </div>
</x-app-layout>
