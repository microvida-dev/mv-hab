<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionSchema;

class DocumentExtractionPromptBuilder
{
    public function build(DocumentExtractionSchema $schema, string $ocrText): string
    {
        $fields = collect($schema->fields)
            ->map(fn (array $definition, string $key): string => $key.' ('.$definition['type']->value.') — '.$definition['label'])
            ->implode("\n");
        $text = mb_substr($this->minimize($ocrText), 0, $this->maxChars());
        $schemaVersion = (string) config('document-ai-extraction.schema_version', '1.0');

        return <<<PROMPT
Extraia apenas os campos solicitados deste documento e devolva apenas JSON válido.

Tipo documental classificado:
{$schema->documentType->value}

Campos solicitados:
{$fields}

Regras:
- Responda apenas com JSON válido.
- Não inclua markdown.
- Não inclua comentários.
- Não inclua texto fora do JSON.
- Não invente valores.
- Se um campo não existir ou estiver ilegível, use null.
- Preserve o valor original em "value".
- Quando possível, devolva "normalized_value".
- A confiança deve estar entre 0 e 1.
- Não tome decisões administrativas.
- Não valide candidatura.

Formato obrigatório:
{"schema_version":"{$schemaVersion}","document_type":"{$schema->documentType->value}","fields":{"field_key":{"value":null,"normalized_value":null,"type":"string","confidence":0.0,"source":"local_ai","requires_review":true}},"flags":[]}

Texto OCR:
{$text}
PROMPT;
    }

    private function minimize(string $ocrText): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $ocrText));
    }

    private function maxChars(): int
    {
        return max(1000, (int) config('document-ai-extraction.ollama.max_chars', 12000));
    }
}
