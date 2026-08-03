<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Relatórios municipais"
            title="Preparar exportação temporal"
            description="Todos os formatos selecionados serão produzidos a partir do mesmo snapshot canónico."
        >
            <x-slot name="actions">
                <a class="mv-button-secondary" href="{{ route('backoffice.reports.temporal-exports.index') }}">Histórico</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    @php
        $selectedFormats = old('formats', $values['formats'] ?? ['csv', 'json']);
        $selectedDatasets = old('datasets', $values['datasets'] ?? ['applications', 'documents', 'findings']);
        $selectedMode = old('mode', $values['mode'] ?? 'current_state');
        $asOfDefault = now('UTC')->format('Y-m-d\TH:i');
    @endphp

    <div class="space-y-6">
        <x-flash-message />

        @if ($errors->any())
            <x-mv.alert tone="danger">
                Existem campos por corrigir antes de preparar a exportação.
            </x-mv.alert>
        @endif

        @if ($preview)
            @php
                $previewFormatLabels = collect($preview->formats)
                    ->map(fn (string $format): string => \App\Enums\ApplicationResultExportFormat::tryFrom($format)?->label() ?? strtoupper($format))
                    ->join(', ');
                $previewDatasetLabels = collect($preview->datasets)
                    ->map(fn (string $dataset): string => \App\Enums\ApplicationResultExportDataset::tryFrom($dataset)?->label() ?? $dataset)
                    ->join(', ');
                $previewSourceReferences = collect($preview->sourceReferences)
                    ->map(function (mixed $reference, string $key): string {
                        $label = (string) str($key)->replace('_', ' ')->title();

                        if (! is_array($reference)) {
                            return $label.': '.(is_bool($reference) ? ($reference ? 'Sim' : 'Não') : (string) $reference);
                        }

                        if (is_string($reference['public_id'] ?? null)) {
                            return $label.': '.$reference['public_id'];
                        }

                        $publicIds = collect($reference)
                            ->filter(fn (mixed $item): bool => is_array($item) && is_string($item['public_id'] ?? null))
                            ->map(fn (array $item): string => $item['public_id'])
                            ->values();

                        return $label.': '.($publicIds->isNotEmpty()
                            ? $publicIds->join(', ')
                            : count($reference).' referência(s)');
                    })
                    ->values();
            @endphp
            <x-mv.section
                title="Pré-visualização"
                description="Esta operação não gerou ficheiros nem carregou documentos."
            >
                <dl class="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <dt class="text-ink-500">Município</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $preview->municipalityName }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Concurso</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $preview->contestCode }} · {{ $preview->contestTitle }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Modo</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $preview->modeLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Caráter</dt>
                        <dd class="mt-1">
                            <x-mv.badge :tone="$preview->official ? 'success' : 'warning'">
                                {{ $preview->official ? 'Oficial' : 'Operacional' }}
                            </x-mv.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Fonte</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $preview->sourceType }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Snapshot previsto</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $preview->snapshotAt->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Estimativa</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $preview->estimatedApplications }} candidatura(s)</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Retenção</dt>
                        <dd class="mt-1 font-medium text-ink-900">Até {{ $preview->expiresAt->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Formatos</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $previewFormatLabels }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-500">Datasets</dt>
                        <dd class="mt-1 font-medium text-ink-900">{{ $previewDatasetLabels }}</dd>
                    </div>
                </dl>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md border border-ink-200 p-4">
                        <h3 class="text-sm font-semibold text-ink-900">Referências da origem</h3>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ink-700">
                            @forelse ($previewSourceReferences as $reference)
                                <li class="break-all">{{ $reference }}</li>
                            @empty
                                <li>Sem referências temporais adicionais.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="rounded-md border border-ink-200 p-4">
                        <h3 class="text-sm font-semibold text-ink-900">Impacto esperado</h3>
                        <p class="mt-2 text-sm text-ink-700">
                            Será criado em fila um pacote ZIP privado com {{ count($preview->formats) }} formato(s)
                            e {{ count($preview->datasets) }} dataset(s), a partir de uma única captura canónica
                            para até {{ $preview->estimatedApplications }} candidatura(s).
                        </p>
                    </div>
                </div>

                @if ($preview->sensitiveFieldsIncluded || $preview->documentFilesRequested)
                    <x-mv.alert class="mt-5" tone="warning">
                        Pedido sensível: {{ $preview->sensitiveFieldsIncluded ? 'campos pessoais confirmados' : 'sem campos pessoais' }};
                        {{ $preview->documentFilesRequested ? 'dossier documental pedido' : 'sem ficheiros documentais' }}.
                    </x-mv.alert>
                @endif

                @if ($preview->warnings !== [])
                    <ul class="mt-5 list-disc space-y-1 pl-5 text-sm text-ink-700">
                        @foreach ($preview->warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                @endif
            </x-mv.section>
        @endif

        <form method="POST" action="{{ route('backoffice.reports.temporal-exports.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="idempotency_token" value="{{ $idempotencyToken }}">

            <x-mv.section title="Origem temporal" description="O Município é determinado pelo utilizador autenticado e nunca pelo pedido do browser.">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="contest_id" value="Concurso" />
                        <select id="contest_id" name="contest_id" required class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Selecione</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}" @selected((string) old('contest_id', $values['contest_id'] ?? '') === (string) $contest->id)>
                                    {{ $contest->code }} · {{ $contest->title }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('contest_id')" />
                    </div>

                    <div>
                        <x-input-label for="mode" value="Modo temporal" />
                        <select id="mode" name="mode" required class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            @foreach ($modes as $mode)
                                <option value="{{ $mode->value }}" @selected($selectedMode === $mode->value)>{{ $mode->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('mode')" />
                    </div>

                    <div>
                        <x-input-label for="batch_public_id" value="Lote selado" />
                        <select id="batch_public_id" name="batch_public_id" class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Não aplicável</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->public_id }}" @selected(old('batch_public_id', $values['batch_public_id'] ?? '') === $batch->public_id)>
                                    {{ $batch->contest?->code }} · {{ $batch->cycle->label() }} #{{ $batch->sequence_number }} · {{ $batch->sealed_at?->format('d/m/Y H:i') }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-500">Obrigatório apenas em “Lote selado”.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('batch_public_id')" />
                    </div>

                    <div>
                        <x-input-label for="phase" value="Fase publicada" />
                        <select id="phase" name="phase" class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Não aplicável</option>
                            @foreach ($phases as $phase)
                                <option value="{{ $phase->value }}" @selected(old('phase', $values['phase'] ?? '') === $phase->value)>{{ $phase->label() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-ink-500">Obrigatória no snapshot de fase.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('phase')" />
                    </div>

                    <div>
                        <x-input-label for="base_batch_public_id" value="Lote base do delta" />
                        <select id="base_batch_public_id" name="base_batch_public_id" class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Não aplicável</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->public_id }}" @selected(old('base_batch_public_id', $values['base_batch_public_id'] ?? '') === $batch->public_id)>
                                    {{ $batch->contest?->code }} · {{ $batch->cycle->label() }} #{{ $batch->sequence_number }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('base_batch_public_id')" />
                    </div>

                    <div>
                        <x-input-label for="target_batch_public_id" value="Lote alvo do delta" />
                        <select id="target_batch_public_id" name="target_batch_public_id" class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="">Não aplicável</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->public_id }}" @selected(old('target_batch_public_id', $values['target_batch_public_id'] ?? '') === $batch->public_id)>
                                    {{ $batch->contest?->code }} · {{ $batch->cycle->label() }} #{{ $batch->sequence_number }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('target_batch_public_id')" />
                    </div>

                    <div>
                        <x-input-label for="since" value="Desde" />
                        <input id="since" name="since" type="datetime-local" value="{{ old('since', $values['since'] ?? '') }}" class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                        <p class="mt-1 text-xs text-ink-500">Obrigatório no delta desde uma data.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('since')" />
                    </div>

                    <div>
                        <x-input-label for="as_of" value="Instante de referência" />
                        <input id="as_of" name="as_of" type="datetime-local" value="{{ old('as_of', $values['as_of'] ?? $asOfDefault) }}" class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                        <p class="mt-1 text-xs text-ink-500">Usado em snapshots publicados, deltas temporais e resultado final.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('as_of')" />
                    </div>
                </div>
            </x-mv.section>

            <x-mv.section title="Formatos e datasets" description="O resultado físico será sempre um ZIP com manifesto, schemas e checksums.">
                <div class="grid gap-6 md:grid-cols-2">
                    <fieldset>
                        <legend class="text-sm font-semibold text-ink-700">Formatos estruturados</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($formats as $format)
                                <label class="flex min-h-11 items-center gap-3 rounded-md border border-ink-200 px-3 py-2 text-sm text-ink-800 focus-within:ring-2 focus-within:ring-mvhab-primary">
                                    <input type="checkbox" name="formats[]" value="{{ $format->value }}" @checked(in_array($format->value, $selectedFormats, true)) class="rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                                    {{ $format->label() }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('formats')" />
                    </fieldset>

                    <fieldset>
                        <legend class="text-sm font-semibold text-ink-700">Dados a incluir</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($datasets as $dataset)
                                <label class="flex min-h-11 items-center gap-3 rounded-md border border-ink-200 px-3 py-2 text-sm text-ink-800 focus-within:ring-2 focus-within:ring-mvhab-primary">
                                    <input type="checkbox" name="datasets[]" value="{{ $dataset->value }}" @checked(in_array($dataset->value, $selectedDatasets, true)) class="rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                                    {{ $dataset->label() }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('datasets')" />
                    </fieldset>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="csv_delimiter" value="Delimitador CSV" />
                        <select id="csv_delimiter" name="csv_delimiter" required class="mt-1 block w-full rounded-md border-ink-300 text-sm focus:border-mvhab-primary focus:ring-mvhab-primary">
                            <option value="semicolon" @selected(old('csv_delimiter', $values['csv_delimiter'] ?? 'semicolon') === 'semicolon')>Ponto e vírgula (;)</option>
                            <option value="comma" @selected(old('csv_delimiter', $values['csv_delimiter'] ?? '') === 'comma')>Vírgula (,)</option>
                            <option value="tab" @selected(old('csv_delimiter', $values['csv_delimiter'] ?? '') === 'tab')>Tabulação</option>
                        </select>
                    </div>
                    <div class="space-y-3 pt-1">
                        <label class="flex items-start gap-3 text-sm text-ink-800">
                            <input type="hidden" name="csv_bom" value="0">
                            <input type="checkbox" name="csv_bom" value="1" @checked(old('csv_bom', $values['csv_bom'] ?? true)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                            <span><strong>Incluir BOM UTF-8</strong><br><span class="text-ink-500">Melhora a abertura do CSV em aplicações de folha de cálculo.</span></span>
                        </label>
                        <label class="flex items-start gap-3 text-sm text-ink-800">
                            <input type="hidden" name="include_unchanged" value="0">
                            <input type="checkbox" name="include_unchanged" value="1" @checked(old('include_unchanged', $values['include_unchanged'] ?? false)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                            <span><strong>Incluir registos sem alteração</strong><br><span class="text-ink-500">Aplicável apenas aos modos delta.</span></span>
                        </label>
                    </div>
                </div>
            </x-mv.section>

            <x-mv.section title="Dados sensíveis e dossier" description="Estas opções estão desativadas por defeito e exigem permissões adicionais.">
                <div class="space-y-4">
                    <label class="flex items-start gap-3 text-sm text-ink-800">
                        <input type="hidden" name="include_sensitive" value="0">
                        <input type="checkbox" name="include_sensitive" value="1" @checked(old('include_sensitive', $values['include_sensitive'] ?? false)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                        <span><strong>Incluir campos pessoais autorizados</strong><br><span class="text-ink-500">Nunca inclui NIF, paths, OCR, notas internas, IBAN ou conteúdo documental.</span></span>
                    </label>
                    <label class="flex items-start gap-3 text-sm text-ink-800">
                        <input type="checkbox" name="sensitive_confirmed" value="1" @checked(old('sensitive_confirmed', $values['sensitive_confirmed'] ?? false)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                        <span>Confirmo a finalidade municipal e o tratamento autorizado dos campos pessoais.</span>
                    </label>
                    <x-input-error :messages="$errors->get('sensitive_confirmed')" />

                    <label class="flex items-start gap-3 text-sm text-ink-800">
                        <input type="hidden" name="include_document_files" value="0">
                        <input type="checkbox" name="include_document_files" value="1" @checked(old('include_document_files', $values['include_document_files'] ?? false)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                        <span><strong>Pedir inclusão de ficheiros documentais</strong><br><span class="text-ink-500">Sem estado antivírus/quarentena verificável, os ficheiros são excluídos e ficam apenas no índice técnico.</span></span>
                    </label>
                    <label class="flex items-start gap-3 text-sm text-ink-800">
                        <input type="checkbox" name="document_files_confirmed" value="1" @checked(old('document_files_confirmed', $values['document_files_confirmed'] ?? false)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                        <span>Confirmo o pedido explícito de dossier documental privado.</span>
                    </label>
                    <x-input-error :messages="$errors->get('document_files_confirmed')" />

                    <label class="flex items-start gap-3 text-sm text-ink-800">
                        <input type="hidden" name="changed_documents_only" value="0">
                        <input type="checkbox" name="changed_documents_only" value="1" @checked(old('changed_documents_only', $values['changed_documents_only'] ?? false)) class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary">
                        <span>Nos modos delta, considerar apenas documentos alterados.</span>
                    </label>
                    <x-input-error :messages="$errors->get('include_document_files')" />
                    <x-input-error :messages="$errors->get('changed_documents_only')" />
                </div>
            </x-mv.section>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="submit"
                    formaction="{{ route('backoffice.reports.temporal-exports.preview') }}"
                    class="mv-button-secondary"
                >
                    Pré-visualizar
                </button>
                <button type="submit" class="mv-button-primary">Gerar em fila</button>
            </div>
        </form>
    </div>
</x-app-layout>
