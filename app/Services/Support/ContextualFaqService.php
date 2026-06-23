<?php

namespace App\Services\Support;

use App\Enums\InteractionType;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\User;
use App\Services\CandidateExperience\CandidateInteractionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContextualFaqService
{
    public function __construct(private readonly CandidateInteractionService $interactions) {}

    /**
     * @return Collection<int, ContextualFaq>
     */
    public function resolve(string $contextKey, ?Contest $contest = null, ?string $search = null): Collection
    {
        return ContextualFaq::query()
            ->published()
            ->forContext($contextKey)
            ->where(function (Builder $query) use ($contest): void {
                $query->whereNull('contest_id');
                if ($contest instanceof Contest) {
                    $query->orWhere('contest_id', $contest->id);
                }
            })
            ->when($search !== null && trim((string) $search) !== '', function (Builder $query) use ($search): Builder {
                $term = '%'.trim((string) $search).'%';

                return $query->where(function (Builder $builder) use ($term): void {
                    $builder->where('question', 'like', $term)
                        ->orWhere('answer', 'like', $term);
                });
            })
            ->with(['category', 'contest'])
            ->orderBy('sort_order')
            ->orderBy('question')
            ->get();
    }

    public function recordView(ContextualFaq $faq, User $user): void
    {
        $this->interactions->record(
            user: $user,
            type: InteractionType::FaqViewed,
            title: 'FAQ contextual consultada',
            description: $faq->question,
            related: $faq,
            contest: $faq->contest,
            actor: $user,
        );
    }
}
