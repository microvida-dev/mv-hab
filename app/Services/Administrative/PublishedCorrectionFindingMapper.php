<?php

namespace App\Services\Administrative;

use App\Enums\CorrectionIssueType;
use App\Enums\CorrectionRequiredAction;
use App\Enums\DocumentStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contract;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use Illuminate\Validation\ValidationException;

class PublishedCorrectionFindingMapper
{
    /**
     * @param  array<string, mixed>  $snapshotPayload
     * @return list<array{
     *     target_type: string,
     *     target_id: int,
     *     issue_type: CorrectionIssueType,
     *     title: string,
     *     description: string|null,
     *     required_action: CorrectionRequiredAction,
     *     is_required: bool,
     *     document_type_id: int|null,
     *     required_document_id: int|null,
     *     sort_order: int
     * }>
     */
    public function map(array $snapshotPayload): array
    {
        $findings = $snapshotPayload['findings'] ?? null;

        if (! is_array($findings)) {
            throw ValidationException::withMessages([
                'correction_request' => 'O snapshot publicado não contém achados estruturados para aperfeiçoamento.',
            ]);
        }

        $items = [];

        foreach (array_values($findings) as $index => $finding) {
            if (! is_array($finding)) {
                continue;
            }

            $status = DocumentStatus::tryFrom((string) ($finding['document_status'] ?? ''));

            if (! in_array($status, [
                DocumentStatus::Missing,
                DocumentStatus::Rejected,
                DocumentStatus::Expired,
            ], true)) {
                continue;
            }

            $targetType = trim((string) ($finding['target_type'] ?? ''));
            $targetId = (int) ($finding['target_id'] ?? 0);
            $title = trim((string) ($finding['title'] ?? ''));

            if (! in_array($targetType, $this->allowedTargetTypes(), true)
                || $targetId <= 0
                || $title === '') {
                throw ValidationException::withMessages([
                    'correction_request' => 'Um achado publicado não possui alvo e título suficientes para gerar o pedido.',
                ]);
            }

            [$issueType, $requiredAction] = match ($status) {
                DocumentStatus::Missing => [
                    CorrectionIssueType::MissingDocument,
                    CorrectionRequiredAction::UploadDocument,
                ],
                DocumentStatus::Rejected => [
                    CorrectionIssueType::RejectedDocument,
                    CorrectionRequiredAction::ReplaceDocument,
                ],
                DocumentStatus::Expired => [
                    CorrectionIssueType::ExpiredDocument,
                    CorrectionRequiredAction::ReplaceDocument,
                ],
            };

            $items[] = [
                'target_type' => $targetType,
                'target_id' => $targetId,
                'issue_type' => $issueType,
                'title' => $title,
                'description' => $this->nullableString($finding['description'] ?? null),
                'required_action' => $requiredAction,
                'is_required' => (bool) ($finding['is_required'] ?? true),
                'document_type_id' => $this->nullablePositiveInt(
                    $finding['document_type_id'] ?? null,
                ),
                'required_document_id' => $this->nullablePositiveInt(
                    $finding['required_document_id'] ?? null,
                ),
                'sort_order' => (int) ($finding['sort_order'] ?? $index + 1),
            ];
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'correction_request' => 'O resultado requer aperfeiçoamento, mas o snapshot não contém achados desconformes acionáveis.',
            ]);
        }

        return $items;
    }

    /** @return list<string> */
    private function allowedTargetTypes(): array
    {
        return [
            (new AdhesionRegistration)->getMorphClass(),
            (new Household)->getMorphClass(),
            (new HouseholdMember)->getMorphClass(),
            (new IncomeRecord)->getMorphClass(),
            (new CurrentHousingSituation)->getMorphClass(),
            (new Application)->getMorphClass(),
            (new Contract)->getMorphClass(),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
