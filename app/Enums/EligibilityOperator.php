<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EligibilityOperator: string
{
    use HasOptions;

    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Between = 'between';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case Exists = 'exists';
    case NotExists = 'not_exists';
    case In = 'in';
    case NotIn = 'not_in';
    case AllRequiredDocumentsSubmitted = 'all_required_documents_submitted';
    case AllRequiredDocumentsValidated = 'all_required_documents_validated';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Igual a',
            self::NotEquals => 'Diferente de',
            self::GreaterThan => 'Maior que',
            self::GreaterThanOrEqual => 'Maior ou igual a',
            self::LessThan => 'Menor que',
            self::LessThanOrEqual => 'Menor ou igual a',
            self::Between => 'Entre',
            self::IsTrue => 'É verdadeiro',
            self::IsFalse => 'É falso',
            self::Exists => 'Existe',
            self::NotExists => 'Não existe',
            self::In => 'Pertence a',
            self::NotIn => 'Não pertence a',
            self::AllRequiredDocumentsSubmitted => 'Todos os documentos submetidos',
            self::AllRequiredDocumentsValidated => 'Todos os documentos validados',
            self::Custom => 'Avaliação específica',
        };
    }
}
