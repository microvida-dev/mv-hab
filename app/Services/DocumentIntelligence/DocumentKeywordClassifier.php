<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\KeywordClassificationResult;
use App\Enums\DocumentAiDocumentType;
use Illuminate\Support\Str;

class DocumentKeywordClassifier
{
    public function classify(string $text): KeywordClassificationResult
    {
        $normalizedText = $this->normalize($text);
        $scores = [];
        $signals = [];

        /** @var array<string, list<string>> $keywordMap */
        $keywordMap = config('document-ai-classification.keywords', []);

        foreach ($keywordMap as $type => $keywords) {
            $score = 0;

            foreach ($keywords as $keyword) {
                $normalizedKeyword = $this->normalize($keyword);

                if ($normalizedKeyword !== '' && str_contains($normalizedText, $normalizedKeyword)) {
                    $score++;
                    $signals[] = 'keyword:'.$type.':'.Str::slug($normalizedKeyword);
                }
            }

            if ($score > 0) {
                $scores[$type] = $score;
            }
        }

        if ($scores === []) {
            return new KeywordClassificationResult(
                documentType: DocumentAiDocumentType::Outro,
                confidence: 0.25,
                signals: ['keyword:no_supported_match'],
                scores: [],
            );
        }

        arsort($scores);
        $type = array_key_first($scores);
        $matches = (int) $scores[$type];
        $confidence = min(0.99, 0.54 + ($matches * 0.12));

        return new KeywordClassificationResult(
            documentType: DocumentAiDocumentType::from((string) $type),
            confidence: round($confidence, 2),
            signals: array_values(array_unique($signals)),
            scores: $scores,
        );
    }

    private function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', Str::lower(Str::ascii($value))));
    }
}
