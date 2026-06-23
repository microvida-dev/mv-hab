<?php

namespace App\Services\DocumentIntelligence;

use App\Models\DocumentAiField;

class DocumentExtractedFieldPresenter
{
    /**
     * @return array{field: DocumentAiField, display_value: string, display_normalized_value: string, is_hidden: bool, is_health_data: bool, is_sensitive: bool}
     */
    public function present(DocumentAiField $field, bool $canViewSensitive, bool $canViewHealth): array
    {
        $metadata = is_array($field->metadata) ? $field->metadata : [];
        $isSensitive = (bool) ($metadata['sensitive'] ?? true);
        $isHealthData = (bool) ($metadata['health_data'] ?? false);

        if ($isHealthData && ! $canViewHealth) {
            return [
                'field' => $field,
                'display_value' => 'Oculto (dados de saúde)',
                'display_normalized_value' => 'Oculto',
                'is_hidden' => true,
                'is_health_data' => true,
                'is_sensitive' => $isSensitive,
            ];
        }

        if ($isSensitive && ! $canViewSensitive) {
            return [
                'field' => $field,
                'display_value' => $this->maskedValue($field),
                'display_normalized_value' => $this->maskedValue($field, true),
                'is_hidden' => false,
                'is_health_data' => $isHealthData,
                'is_sensitive' => true,
            ];
        }

        return [
            'field' => $field,
            'display_value' => $field->value ?? '-',
            'display_normalized_value' => $field->normalized_value ?? '-',
            'is_hidden' => false,
            'is_health_data' => $isHealthData,
            'is_sensitive' => $isSensitive,
        ];
    }

    private function maskedValue(DocumentAiField $field, bool $normalized = false): string
    {
        $value = $normalized ? $field->normalized_value : $field->value;

        if ($value === null || $value === '') {
            return '-';
        }

        if (in_array($field->key, ['nif', 'document_number', 'beneficiary_number'], true)) {
            return str_repeat('*', max(3, min(8, strlen($value) - 3))).substr($value, -3);
        }

        if (in_array($field->key, ['address'], true)) {
            return 'Oculto';
        }

        return '***';
    }
}
