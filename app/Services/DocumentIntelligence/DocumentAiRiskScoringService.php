<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiScoreResult;
use App\Models\DocumentAiScore;

class DocumentAiRiskScoringService
{
    public const GREEN = 'green';

    public const YELLOW = 'yellow';

    public const RED = 'red';

    public function labelForScore(int $score, bool $requiresManualReview = false): string
    {
        if ($score >= 75 && ! $requiresManualReview) {
            return self::GREEN;
        }

        if ($score >= 40) {
            return self::YELLOW;
        }

        return self::RED;
    }

    public function labelForResult(DocumentAiScoreResult $result): string
    {
        return $this->labelForScore($result->score, $result->requiresManualReview);
    }

    public function labelForModel(DocumentAiScore $score): string
    {
        return $this->labelForScore((int) $score->score, (bool) $score->requires_manual_review);
    }
}
