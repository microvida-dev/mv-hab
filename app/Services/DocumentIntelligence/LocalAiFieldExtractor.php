<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Data\DocumentIntelligence\DocumentExtractionSchema;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiExtractionSource;
use Illuminate\Support\Facades\Http;
use Throwable;

class LocalAiFieldExtractor
{
    public function __construct(
        private readonly DocumentExtractionPromptBuilder $promptBuilder,
        private readonly DocumentFieldNormalizer $normalizer,
    ) {}

    /**
     * @return array{fields: list<ExtractedDocumentField>, flags: list<DocumentExtractionFlag>}
     */
    public function extract(string $ocrText, DocumentExtractionSchema $schema): array
    {
        if (! (bool) config('document-ai-extraction.ollama.enabled', false)) {
            return ['fields' => [], 'flags' => []];
        }

        try {
            $response = Http::timeout((int) config('document-ai-extraction.ollama.timeout', 120))
                ->acceptJson()
                ->post(rtrim((string) config('document-ai.ollama.base_url', 'http://127.0.0.1:11434'), '/').'/api/generate', [
                    'model' => (string) config('document-ai-extraction.ollama.model', 'gemma3:4b'),
                    'prompt' => $this->promptBuilder->build($schema, $ocrText),
                    'stream' => false,
                    'format' => 'json',
                ]);
        } catch (Throwable) {
            return [
                'fields' => [],
                'flags' => [new DocumentExtractionFlag('field_ai_request_failed', 'low', 'IA local indisponível para extração estruturada.')],
            ];
        }

        if (! $response->successful()) {
            return [
                'fields' => [],
                'flags' => [new DocumentExtractionFlag('field_ai_http_failed', 'low', 'IA local respondeu com falha técnica.')],
            ];
        }

        $payload = $response->json();
        $json = is_array($payload) && is_string($payload['response'] ?? null) ? $payload['response'] : $response->body();

        try {
            $decoded = json_decode($this->extractJsonObject($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [
                'fields' => [],
                'flags' => [new DocumentExtractionFlag('field_ai_invalid_json', 'low', 'IA local devolveu JSON inválido.')],
            ];
        }

        if (! is_array($decoded) || ! is_array($decoded['fields'] ?? null)) {
            return [
                'fields' => [],
                'flags' => [new DocumentExtractionFlag('field_ai_invalid_payload', 'low', 'IA local devolveu payload sem campos.')],
            ];
        }

        $fields = [];
        $flags = [];

        foreach ($decoded['fields'] as $key => $field) {
            if (! is_string($key) || ! is_array($field) || ! isset($schema->fields[$key])) {
                continue;
            }

            $definition = $schema->fields[$key];
            $raw = $this->scalarOrNull($field['value'] ?? null);
            $normalization = $this->normalizer->normalize($key, $definition['type'], $raw);
            $confidence = $this->confidence($field['confidence'] ?? null);
            $requiresReview = (bool) ($field['requires_review'] ?? false) || (bool) $normalization['requires_review'];

            foreach ($normalization['flags'] as $flag) {
                $flags[] = $flag;
            }

            $fields[] = new ExtractedDocumentField(
                key: $key,
                label: $definition['label'],
                type: $definition['type'],
                value: $normalization['value'],
                normalizedValue: $normalization['normalized_value'],
                confidence: $confidence,
                source: DocumentAiExtractionSource::LocalAi,
                requiresReview: $requiresReview,
                sensitive: (bool) $definition['sensitive'],
                healthData: (bool) $definition['health_data'],
            );
        }

        return ['fields' => $fields, 'flags' => $flags];
    }

    private function extractJsonObject(string $response): string
    {
        $start = strpos($response, '{');
        $end = strrpos($response, '}');

        if ($start === false || $end === false || $end < $start) {
            return $response;
        }

        return substr($response, $start, ($end - $start) + 1);
    }

    private function scalarOrNull(mixed $value): string|int|float|bool|null
    {
        return is_scalar($value) || $value === null ? $value : null;
    }

    private function confidence(mixed $value): float
    {
        return is_numeric($value) ? round(max(0.0, min(1.0, (float) $value)), 2) : 0.0;
    }
}
