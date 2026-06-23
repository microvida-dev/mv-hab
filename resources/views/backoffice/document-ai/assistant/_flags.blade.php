<section class="mv-surface overflow-hidden">
    <div class="border-b border-ink-100 px-6 py-4">
        <h2 class="text-lg font-semibold text-ink-900">Indicadores de risco</h2>
    </div>
    <table class="min-w-full divide-y divide-ink-100 text-sm">
        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
            <tr>
                <th class="px-5 py-3">Indicador</th>
                <th class="px-5 py-3">Severidade</th>
                <th class="px-5 py-3">Impacto</th>
                <th class="px-5 py-3">Origem</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
            @forelse ($flags as $flag)
                <tr>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-ink-900">{{ $flag->details['label'] ?? $flag->code }}</p>
                        <p class="text-xs text-ink-500">{{ $flag->message }}</p>
                    </td>
                    <td class="px-5 py-4 text-ink-700">{{ $flag->severity }}</td>
                    <td class="px-5 py-4 text-amber-700">{{ $flag->score_impact ? '-'.$flag->score_impact : '0' }}</td>
                    <td class="px-5 py-4 text-ink-700">{{ $flag->detected_by ?? 'sistema' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-8 text-center text-ink-500">Sem indicadores de risco registados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
