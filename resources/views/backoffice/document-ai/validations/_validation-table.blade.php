<section class="mv-surface overflow-hidden">
    <table class="min-w-full divide-y divide-ink-100 text-sm">
        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
            <tr>
                <th class="px-5 py-3">Verificação</th>
                <th class="px-5 py-3">Estado</th>
                <th class="px-5 py-3">Severidade</th>
                <th class="px-5 py-3">Declarado</th>
                <th class="px-5 py-3">Documento</th>
                <th class="px-5 py-3">Confiança</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @forelse ($presentedValidations as $validation)
                <tr>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-ink-900">{{ $validation['label'] }}</p>
                        <p class="text-xs text-ink-500">{{ $validation['group'] }}</p>
                    </td>
                    <td class="px-5 py-4">{{ $validation['status'] }}</td>
                    <td class="px-5 py-4">{{ $validation['severity'] }}</td>
                    <td class="px-5 py-4 text-ink-700">{{ $validation['candidate_value'] }}</td>
                    <td class="px-5 py-4 text-ink-700">{{ $validation['extracted_value'] }}</td>
                    <td class="px-5 py-4">{{ $validation['confidence'] !== null ? number_format($validation['confidence'] * 100, 0).'%' : '-' }}</td>
                    <td class="px-5 py-4 text-right">
                        <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.document-ai.validations.validation', $validation['id']) }}">Detalhe</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-ink-500">Ainda não existem validações para esta candidatura.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
