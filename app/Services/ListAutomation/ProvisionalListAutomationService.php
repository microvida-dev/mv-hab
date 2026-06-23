<?php

namespace App\Services\ListAutomation;

use App\Enums\AnonymizationMode;
use App\Enums\ListAutomationStatus;
use App\Enums\ListAutomationType;
use App\Models\Contest;
use App\Models\ListAutomationRun;
use App\Models\User;
use App\Services\Lists\ProvisionalListService;
use Illuminate\Validation\ValidationException;

class ProvisionalListAutomationService
{
    public function __construct(
        private readonly ListAutomationValidator $validator,
        private readonly ListAutomationRunService $runs,
        private readonly ProvisionalListService $lists,
    ) {}

    public function run(Contest $contest, User $actor): ListAutomationRun
    {
        $validation = $this->validator->provisional($contest);

        if ($validation['errors'] !== [] || $validation['snapshot'] === null) {
            throw ValidationException::withMessages(['contest_id' => implode(' ', $validation['errors'])]);
        }

        $snapshot = $validation['snapshot'];
        $list = $this->lists->generateFromSnapshot([
            'ranking_snapshot_id' => $snapshot->id,
            'title' => 'Lista provisória automática — '.$contest->title,
            'description' => 'A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.',
            'anonymization_mode' => AnonymizationMode::PublicIdentifierOnly->value,
            'public_visibility' => false,
        ], $actor);

        return $this->runs->create($contest, ListAutomationType::Provisional, $actor, [
            'status' => ListAutomationStatus::Generated,
            'source_ranking_snapshot_id' => $snapshot->id,
            'source_provisional_list_id' => $list->id,
            'total_candidates' => $list->entries()->count(),
            'included_count' => $list->entries()->count(),
            'excluded_count' => 0,
            'warnings' => $validation['warnings'],
            'criteria_snapshot' => [
                'ranking_snapshot_id' => $snapshot->id,
                'copy' => 'A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.',
            ],
            'result_payload' => [
                'provisional_list_id' => $list->id,
                'list_number' => $list->list_number,
            ],
        ]);
    }
}
