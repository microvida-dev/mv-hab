<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiScoreLabel: string
{
    use HasOptions;

    case MuitoConfiavel = 'muito_confiavel';
    case ConfiavelComAtencao = 'confiavel_com_atencao';
    case RequerRevisao = 'requer_revisao';
    case BaixaConfianca = 'baixa_confianca';
    case Critico = 'critico';

    public function label(): string
    {
        return match ($this) {
            self::MuitoConfiavel => 'Muito confiável',
            self::ConfiavelComAtencao => 'Confiável com atenção',
            self::RequerRevisao => 'Requer revisão',
            self::BaixaConfianca => 'Baixa confiança',
            self::Critico => 'Crítico para revisão',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 90 => self::MuitoConfiavel,
            $score >= 75 => self::ConfiavelComAtencao,
            $score >= 60 => self::RequerRevisao,
            $score >= 40 => self::BaixaConfianca,
            default => self::Critico,
        };
    }
}
