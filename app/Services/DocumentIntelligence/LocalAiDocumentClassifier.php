<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\AiClassificationResult;
use Illuminate\Support\Facades\Http;
use Throwable;

class LocalAiDocumentClassifier
{
    public function __construct(
        private readonly DocumentClassificationPromptBuilder $promptBuilder,
        private readonly DocumentClassificationResultNormalizer $normalizer,
    ) {}

    public function classify(string $ocrText): AiClassificationResult
    {
        if (! (bool) config('document-ai.ollama.enabled', false)) {
            return new AiClassificationResult(
                documentType: null,
                label: null,
                confidence: 0.0,
                signals: ['ai:ollama:disabled'],
                requiresManualReview: false,
                source: 'ollama',
                reason: 'ollama_disabled',
                failureCode: 'ollama_disabled',
            );
        }

        try {
            $response = Http::timeout((int) config('document-ai.ollama.timeout', 120))
                ->acceptJson()
                ->post(rtrim((string) config('document-ai.ollama.base_url', 'http://127.0.0.1:11434'), '/').'/api/generate', [
                    'model' => (string) config('document-ai.ollama.model', 'gemma3:4b'),
                    'prompt' => $this->promptBuilder->build($ocrText),
                    'stream' => false,
                    'format' => 'json',
                ]);
        } catch (Throwable) {
            return new AiClassificationResult(
                documentType: null,
                label: null,
                confidence: 0.0,
                signals: ['ai:ollama:request_failed'],
                requiresManualReview: true,
                source: 'ollama',
                reason: 'ollama_request_failed',
                failureCode: 'ollama_request_failed',
            );
        }

        if (! $response->successful()) {
            return new AiClassificationResult(
                documentType: null,
                label: null,
                confidence: 0.0,
                signals: ['ai:ollama:http_failed'],
                requiresManualReview: true,
                source: 'ollama',
                reason: 'ollama_http_failed',
                rawResponse: ['status' => $response->status()],
                failureCode: 'ollama_http_failed',
            );
        }

        $payload = $response->json();
        $json = is_array($payload) && is_string($payload['response'] ?? null) ? $payload['response'] : $response->body();

        return $this->normalizer->fromJson($json, 'ollama');
    }
}
