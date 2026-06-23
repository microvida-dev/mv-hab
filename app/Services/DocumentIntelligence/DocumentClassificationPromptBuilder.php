<?php

namespace App\Services\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;

class DocumentClassificationPromptBuilder
{
    public function build(string $ocrText): string
    {
        $categories = collect(DocumentAiDocumentType::cases())
            ->map(fn (DocumentAiDocumentType $type): string => $type->value.' = '.$type->label())
            ->implode("\n");
        $text = mb_substr($ocrText, 0, $this->maxPromptChars());

        return <<<PROMPT
Classifique este documento numa das categorias suportadas e devolva apenas JSON.

Categorias suportadas:
{$categories}

Formato obrigatório:
{"document_type":"cartao_cidadao","label":"Cartão de Cidadão","confidence":0.95,"reason":"sinais utilizados"}

Texto OCR:
{$text}
PROMPT;
    }

    private function maxPromptChars(): int
    {
        return max(500, (int) config('document-ai-classification.max_prompt_chars', 6000));
    }
}
