<section class="mv-surface overflow-hidden">
    <div class="border-b border-ink-100 px-6 py-4">
        <h2 class="text-lg font-semibold text-ink-900">Campos extraídos</h2>
    </div>
    <table class="min-w-full divide-y divide-ink-100 text-sm">
        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
            <tr>
                <th class="px-5 py-3">Campo</th>
                <th class="px-5 py-3">Valor extraído</th>
                <th class="px-5 py-3">Valor normalizado</th>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Fonte</th>
                <th class="px-5 py-3">Confiança</th>
                <th class="px-5 py-3">Revisão</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @forelse ($presentedFields as $item)
                @php
                    /** @var \App\Models\DocumentAiField $field */
                    $field = $item['field'];
                @endphp
                <tr>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-ink-900">{{ $field->label ?? $field->key }}</p>
                        <p class="text-xs text-ink-500">{{ $field->key }}</p>
                    </td>
                    <td class="max-w-xs px-5 py-4">
                        <span class="break-words">{{ $item['display_value'] }}</span>
                    </td>
                    <td class="max-w-xs px-5 py-4">
                        <span class="break-words">{{ $item['display_normalized_value'] }}</span>
                    </td>
                    <td class="px-5 py-4">{{ $field->value_type ?? '-' }}</td>
                    <td class="px-5 py-4">{{ $field->source ?? '-' }}</td>
                    <td class="px-5 py-4">{{ $field->confidence !== null ? number_format((float) $field->confidence * 100, 0).'%' : '-' }}</td>
                    <td class="px-5 py-4">
                        @if ($field->requires_review)
                            <span class="text-amber-700">Requer revisão</span>
                        @else
                            <span class="text-civic-700">Sem revisão</span>
                        @endif
                        @if ($item['is_health_data'])
                            <p class="text-xs text-ink-500">Dado de saúde</p>
                        @elseif ($item['is_sensitive'])
                            <p class="text-xs text-ink-500">Dado sensível</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        @can('markForReview', $field)
                            <form method="POST" action="{{ route('backoffice.document-ai.fields.review', $field) }}" class="inline">
                                @csrf
                                <input type="hidden" name="reason" value="Revisão manual solicitada no detalhe da extração IA.">
                                <button type="submit" class="font-semibold text-civic-700">Rever</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-ink-500">Sem campos estruturados extraídos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
