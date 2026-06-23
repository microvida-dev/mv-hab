<?php

namespace App\Services\Simulator;

class SimulationMessageService
{
    public const PUBLIC_NOTICE = 'A simulação apresentada é meramente indicativa e não substitui a análise formal dos serviços municipais. A elegibilidade, tipologia, renda e possibilidade de candidatura dependem da validação dos dados, documentos e regras aplicáveis ao concurso.';

    public const SHORT_NOTICE = 'Esta simulação é indicativa e não substitui a análise formal dos serviços municipais. A decisão final depende da validação dos dados, documentos e regras aplicáveis ao concurso.';

    /**
     * @return array<string, string>
     */
    public function notices(): array
    {
        return [
            'public' => self::PUBLIC_NOTICE,
            'short' => self::SHORT_NOTICE,
        ];
    }
}
