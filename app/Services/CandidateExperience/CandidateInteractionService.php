<?php

namespace App\Services\CandidateExperience;

use App\Enums\InteractionType;
use App\Models\Application;
use App\Models\CandidateInteraction;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CandidateInteractionService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        User $user,
        InteractionType $type,
        string $title,
        ?string $description = null,
        ?Model $related = null,
        ?Application $application = null,
        ?Contest $contest = null,
        ?HousingUnit $housingUnit = null,
        ?User $actor = null,
        array $metadata = [],
    ): CandidateInteraction {
        $interaction = new CandidateInteraction([
            'interaction_type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
        $interaction->forceFill([
            'user_id' => $user->id,
            'application_id' => $application?->id,
            'contest_id' => $contest?->id,
            'housing_unit_id' => $housingUnit?->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'created_by' => $actor?->id,
            'created_at' => now(),
        ])->save();

        return $interaction->refresh();
    }

    /**
     * @return Collection<int, CandidateInteraction>
     */
    public function forCandidate(User $user): Collection
    {
        return CandidateInteraction::query()
            ->forUser($user)
            ->with(['application', 'contest', 'housingUnit'])
            ->latest('occurred_at')
            ->get();
    }

    /**
     * @return Collection<int, CandidateInteraction>
     */
    public function forApplication(Application $application): Collection
    {
        return CandidateInteraction::query()
            ->where('application_id', $application->id)
            ->with(['user', 'contest', 'housingUnit'])
            ->latest('occurred_at')
            ->get();
    }
}
