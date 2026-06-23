<?php

namespace App\Services\DocumentIntelligence;

use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use App\Models\DocumentAiSuggestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentAiAssistantDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, DocumentAiScore>
     */
    public function scores(array $filters): LengthAwarePaginator
    {
        return DocumentAiScore::query()
            ->with(['analysis.documentSubmission.documentType', 'application.user', 'application.contest'])
            ->when($filters['label'] ?? null, fn ($query, string $label) => $query->where('label', $label))
            ->when(array_key_exists('requires_review', $filters), fn ($query) => $query->where('requires_manual_review', (bool) $filters['requires_review']))
            ->when($filters['flag'] ?? null, fn ($query, string $flag) => $query->whereHas('analysis.flags', fn ($flags) => $flags->where('code', $flag)))
            ->when($filters['application'] ?? null, function ($query, string $value): void {
                $query->whereHas('application', function ($applications) use ($value): void {
                    $applications->where('application_number', 'like', "%{$value}%")
                        ->orWhere('public_id', 'like', "%{$value}%")
                        ->orWhereHas('user', fn ($users) => $users->where('name', 'like', "%{$value}%"));
                });
            })
            ->when($filters['from'] ?? null, fn ($query, string $value) => $query->whereDate('calculated_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($query, string $value) => $query->whereDate('calculated_at', '<=', $value))
            ->latest('calculated_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        return [
            'scores' => DocumentAiScore::query()->count(),
            'requires_review' => DocumentAiScore::query()->requiresReview()->count(),
            'low_confidence' => DocumentAiScore::query()->where('score', '<', 60)->count(),
            'open_suggestions' => DocumentAiSuggestion::query()->open()->count(),
        ];
    }

    public function analysisForShow(DocumentAiAnalysis $analysis): DocumentAiAnalysis
    {
        return $analysis->loadMissing([
            'documentSubmission.documentType',
            'latestScore.suggestions',
            'flags',
            'validations',
            'suggestions',
        ]);
    }
}
