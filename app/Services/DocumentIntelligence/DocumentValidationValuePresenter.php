<?php

namespace App\Services\DocumentIntelligence;

use App\Models\DocumentAiValidation;
use Illuminate\Support\Str;

class DocumentValidationValuePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(DocumentAiValidation $validation, bool $canViewSensitive, bool $canViewHealth): array
    {
        $metadata = is_array($validation->metadata) ? $validation->metadata : [];
        $isSensitive = (bool) ($metadata['sensitive'] ?? true);
        $isHealth = (bool) ($metadata['health_data'] ?? false);

        return [
            'id' => $validation->id,
            'label' => $validation->label,
            'group' => $validation->validation_group->label(),
            'status' => $validation->status->label(),
            'severity' => $validation->severity?->label() ?? '-',
            'candidate_value' => $this->displayValue($validation->candidate_value, $isSensitive, $isHealth, $canViewSensitive, $canViewHealth),
            'extracted_value' => $this->displayValue($validation->extracted_value, $isSensitive, $isHealth, $canViewSensitive, $canViewHealth),
            'confidence' => $validation->confidence !== null ? (float) $validation->confidence : null,
            'message' => $validation->message,
            'recommendation' => $validation->recommendation,
            'requires_manual_review' => (bool) $validation->requires_manual_review,
            'is_sensitive' => $isSensitive,
            'is_health' => $isHealth,
        ];
    }

    private function displayValue(?string $value, bool $isSensitive, bool $isHealth, bool $canViewSensitive, bool $canViewHealth): string
    {
        if ($isHealth && ! $canViewHealth) {
            return 'Dados de saúde ocultos';
        }

        if ($isSensitive && ! $canViewSensitive) {
            return $value === null || $value === '' ? '-' : $this->masked($value);
        }

        return $value === null || $value === '' ? '-' : $value;
    }

    private function masked(string $value): string
    {
        $length = mb_strlen($value);

        if ($length <= 4) {
            return '***';
        }

        return Str::substr($value, 0, 1).'***'.Str::substr($value, -1);
    }
}
