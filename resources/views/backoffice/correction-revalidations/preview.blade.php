<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Confirmação obrigatória"
            title="Pré-visualização da segunda análise"
            description="A selagem cria um snapshot imutável; a publicação permanece uma ação separada."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-3">
                <x-mv.stat-card label="Pedido" :value="$preview['request']->request_number" />
                <x-mv.stat-card label="Resultado agregado" :value="$preview['aggregate_result']->label()" />
                <x-mv.stat-card label="Elementos revistos" :value="count($preview['item_snapshot']['decisions'])" />
            </div>

            <x-mv.alert tone="warning">
                A selagem não resolve o pedido e não produz admissão, exclusão ou elegibilidade automática. O resultado só se torna formal após publicação e projeção.
            </x-mv.alert>

            <x-mv.section title="Fundamentação do fecho">
                <p class="whitespace-pre-line text-sm leading-6 text-ink-700">{{ $preview['reason'] }}</p>
            </x-mv.section>

            <x-mv.section title="Integridade">
                <dl class="grid gap-4 text-sm md:grid-cols-2">
                    <div>
                        <dt class="text-ink-500">Fingerprint das fontes</dt>
                        <dd class="mt-1 break-all font-mono text-xs text-ink-800">{{ $preview['source_fingerprint'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Hash coletivo</dt>
                        <dd class="mt-1 break-all font-mono text-xs text-ink-800">{{ $preview['batch_snapshot_hash'] }}</dd>
                    </div>
                </dl>
            </x-mv.section>

            <x-mv.section title="Resumo diferencial">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-mv.stat-card label="Mantidos" :value="count($preview['item_snapshot']['carried_forward_items'])" />
                    <x-mv.stat-card label="Documentos alterados" :value="count($preview['item_snapshot']['changed_items'])" />
                    <x-mv.stat-card label="Justificações" :value="count($preview['item_snapshot']['justification_items'])" />
                    <x-mv.stat-card label="Dependências" :value="count($preview['item_snapshot']['dependency_affected_items'])" />
                </div>
            </x-mv.section>

            <x-mv.section>
                <form method="POST" action="{{ route('backoffice.correction-revalidations.seal', $preview['request']) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="reason" value="{{ $preview['reason'] }}">
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">

                    <x-mv.checkbox-card
                        name="confirm_seal"
                        label="Confirmo o selamento imutável desta segunda análise e a separação da publicação formal."
                        align="start"
                        required
                    />

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <a href="{{ route('backoffice.correction-requests.show', $preview['request']) }}" class="mv-button-secondary">Voltar sem alterar</a>
                        <button type="submit" class="mv-button-primary">Selar segunda análise</button>
                    </div>
                </form>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
