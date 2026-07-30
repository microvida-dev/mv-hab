<?php

namespace App\Services\Administrative;

use App\Enums\DocumentStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Support\Collection;
use UnexpectedValueException;

class ApplicationReviewReadinessService
{
    public function __construct(
        private readonly DocumentChecklistService $checklistService,
    ) {}

    /**
     * @return array{
     *     ready: bool,
     *     total_required: int,
     *     validated: int,
     *     submitted: int,
     *     under_review: int,
     *     missing: int,
     *     rejected: int,
     *     expired: int,
     *     blockers: list<string>
     * }
     */
    public function forProcess(
        AdministrativeProcess $process,
    ): array {
        $application = $process->application;

        if (! $application instanceof Application) {
            return [
                'ready' => false,
                'total_required' => 0,
                'validated' => 0,
                'submitted' => 0,
                'under_review' => 0,
                'missing' => 0,
                'rejected' => 0,
                'expired' => 0,
                'blockers' => [
                    'A candidatura associada ao processo não está disponível.',
                ],
            ];
        }

        $checklist = $this->checklistService->forApplication(
            $application,
        );

        $items = $checklist['items'] ?? null;

        if (! $items instanceof Collection) {
            throw new UnexpectedValueException(
                'A checklist documental não devolveu uma coleção de itens.',
            );
        }

        $counts = [
            'validated' => 0,
            'submitted' => 0,
            'under_review' => 0,
            'missing' => 0,
            'rejected' => 0,
            'expired' => 0,
        ];
        $totalRequired = 0;

        foreach ($items as $item) {
            if (! is_array($item)
                || ($item['is_required'] ?? false) !== true) {
                continue;
            }

            $totalRequired++;
            $status = $item['status'] ?? DocumentStatus::Missing;

            if (! $status instanceof DocumentStatus) {
                $status = DocumentStatus::tryFrom((string) $status)
                    ?? DocumentStatus::Missing;
            }

            match ($status) {
                DocumentStatus::Validated => $counts['validated']++,
                DocumentStatus::Submitted => $counts['submitted']++,
                DocumentStatus::UnderReview => $counts['under_review']++,
                DocumentStatus::Rejected => $counts['rejected']++,
                DocumentStatus::Expired => $counts['expired']++,
                DocumentStatus::Missing => $counts['missing']++,
                default => $counts['missing']++,
            };
        }

        $blockers = [];

        $this->appendBlocker(
            $blockers,
            $counts['missing'],
            'documento(s) obrigatório(s) em falta',
        );
        $this->appendBlocker(
            $blockers,
            $counts['submitted'],
            'documento(s) ainda por analisar',
        );
        $this->appendBlocker(
            $blockers,
            $counts['under_review'],
            'documento(s) em análise',
        );
        $this->appendBlocker(
            $blockers,
            $counts['rejected'],
            'documento(s) rejeitado(s)',
        );
        $this->appendBlocker(
            $blockers,
            $counts['expired'],
            'documento(s) expirado(s)',
        );

        return [
            'ready' => $blockers === []
                && $counts['validated'] === $totalRequired,
            'total_required' => $totalRequired,
            'validated' => $counts['validated'],
            'submitted' => $counts['submitted'],
            'under_review' => $counts['under_review'],
            'missing' => $counts['missing'],
            'rejected' => $counts['rejected'],
            'expired' => $counts['expired'],
            'blockers' => $blockers,
        ];
    }

    /**
     * @param  list<string>  $blockers
     */
    private function appendBlocker(
        array &$blockers,
        int $count,
        string $label,
    ): void {
        if ($count > 0) {
            $blockers[] = $count.' '.$label;
        }
    }
}
