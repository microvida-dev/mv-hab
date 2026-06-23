<?php

namespace App\Services\Lists;

use App\Enums\AnonymizationMode;
use App\Models\Application;
use App\Models\DefinitiveListEntry;
use App\Models\ProvisionalListEntry;
use Illuminate\Support\Str;

class ListAnonymizationService
{
    public function publicIdentifier(Application $application, int|string|null $listId = null): string
    {
        $seed = implode('|', [
            $application->public_id,
            $application->application_number,
            $application->created_at?->timestamp,
            $listId,
        ]);

        return 'CAND-'.now()->format('Y').'-'.Str::upper(substr(hash('sha256', $seed), 0, 10));
    }

    public function maskedApplicationNumber(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $suffix = Str::of($number)->replace('-', '')->substr(-4);

        return 'CAND-****-'.$suffix;
    }

    public function maskedName(?string $name, AnonymizationMode|string|null $mode = null): ?string
    {
        $mode = $mode instanceof AnonymizationMode ? $mode : AnonymizationMode::tryFrom((string) $mode);

        if ($name === null || $mode !== AnonymizationMode::PartialName) {
            return null;
        }

        $parts = collect(explode(' ', trim($name)))->filter()->values();

        if ($parts->isEmpty()) {
            return null;
        }

        $first = Str::substr($parts->first(), 0, 1).'.';
        $last = $parts->count() > 1 ? Str::substr($parts->last(), 0, 1).'.' : '';

        return trim($first.' '.$last);
    }

    /**
     * @return array<string|int, mixed>
     */
    public function publicPayload(ProvisionalListEntry|DefinitiveListEntry $entry, AnonymizationMode|string|null $mode = null): array
    {
        $mode = $mode instanceof AnonymizationMode ? $mode : AnonymizationMode::tryFrom((string) $mode) ?? AnonymizationMode::PublicIdentifierOnly;

        return [
            'public_identifier' => $entry->public_identifier,
            'application_number' => in_array($mode, [AnonymizationMode::ApplicationNumberOnly, AnonymizationMode::MaskedApplicationNumber], true)
                ? $entry->application_number_masked
                : null,
            'candidate_name' => $mode === AnonymizationMode::PartialName ? $entry->candidate_name_masked : null,
            'rank_position' => $entry->rank_position,
            'status' => $entry->status->label(),
            'entry_type' => $entry->entry_type->label(),
            'decision_summary' => $entry->decision_summary,
            'exclusion_reason' => $entry->exclusion_reason ? Str::limit(strip_tags($entry->exclusion_reason), 180) : null,
        ];
    }
}
