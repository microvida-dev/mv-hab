<?php

namespace App\Services\ListAutomation;

use App\Enums\ProvisionalListStatus;
use App\Enums\RankingSnapshotStatus;
use App\Models\Contest;
use App\Models\ProvisionalList;
use App\Models\RankingSnapshot;

class ListAutomationValidator
{
    /**
     * @return array{snapshot:RankingSnapshot|null,warnings:list<string>,errors:list<string>}
     */
    public function provisional(Contest $contest): array
    {
        $snapshot = RankingSnapshot::query()
            ->withCount('entries')
            ->where('contest_id', $contest->id)
            ->whereIn('status', [RankingSnapshotStatus::Internal->value, RankingSnapshotStatus::Locked->value])
            ->latest('generated_at')
            ->first();

        $errors = [];
        if (! $snapshot instanceof RankingSnapshot) {
            $errors[] = 'Não existe snapshot de ranking interno ou bloqueado para o concurso.';
        } elseif ((int) $snapshot->entries_count === 0) {
            $errors[] = 'O snapshot de ranking não tem entradas.';
        }

        return [
            'snapshot' => $snapshot,
            'warnings' => [],
            'errors' => $errors,
        ];
    }

    /**
     * @return array{provisional:ProvisionalList|null,warnings:list<string>,errors:list<string>}
     */
    public function definitive(Contest $contest): array
    {
        $provisional = ProvisionalList::query()
            ->where('contest_id', $contest->id)
            ->where('status', ProvisionalListStatus::ComplaintPeriodClosed->value)
            ->latest('generated_at')
            ->first();

        $errors = [];
        if (! $provisional instanceof ProvisionalList) {
            $errors[] = 'Não existe lista provisória com prazo de reclamação fechado.';
        }

        return [
            'provisional' => $provisional,
            'warnings' => [],
            'errors' => $errors,
        ];
    }
}
