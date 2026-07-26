@php
    $severityLabel = static function (mixed $severity): string {
        if ($severity instanceof \App\Enums\DocumentAiRiskSeverity) {
            return $severity->label();
        }

        return \App\Enums\DocumentAiRiskSeverity::tryFrom((string) $severity)?->label()
            ?? str((string) $severity)->replace('_', ' ')->headline();
    };

    $flagLabel = static function (mixed $code, array $details = []): string {
        if ($code instanceof \App\Enums\DocumentAiRiskFlagCode) {
            return $code->label();
        }

        $codeValue = (string) $code;

        return \App\Enums\DocumentAiRiskFlagCode::tryFrom($codeValue)?->label()
            ?? ($details['label'] ?? str($codeValue)->replace('_', ' ')->headline());
    };

    $originLabel = static function (?string $origin): string {
        return match ($origin) {
            'document_quality_analyzer' => 'Análise de qualidade documental',
            'document_duplicate_detector' => 'Deteção de duplicados',
            'candidate_validation' => 'Cruzamento com candidatura',
            'document_risk_flag_detector' => 'Análise de risco documental',
            'document_ai_pipeline' => 'Pipeline documental',
            'system', 'sistema', null, '' => 'Sistema',
            default => str($origin)->replace('_', ' ')->headline(),
        };
    };
@endphp

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
                        <p class="font-semibold text-ink-900">
                            {{ $flagLabel($flag->code, $flag->details ?? []) }}
                        </p>
                        <p class="text-xs text-ink-500">{{ $flag->message }}</p>
                    </td>
                    <td class="px-5 py-4 text-ink-700">
                        {{ $severityLabel($flag->severity) }}
                    </td>
                    <td class="px-5 py-4 text-amber-700">
                        {{ $flag->score_impact ? '-'.$flag->score_impact : '0' }}
                    </td>
                    <td class="px-5 py-4 text-ink-700">
                        {{ $originLabel($flag->detected_by) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-8 text-center text-ink-500">
                        Sem indicadores de risco registados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
