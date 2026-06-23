<?php

namespace App\Services\ListAutomation;

use App\Enums\ListAutomationStatus;
use App\Enums\ListAutomationType;
use App\Models\Contest;
use App\Models\ListAutomationRun;
use App\Models\User;
use App\Services\Lists\DefinitiveListService;
use Illuminate\Validation\ValidationException;

class DefinitiveListAutomationService
{
    public function __construct(
        private readonly ListAutomationValidator $validator,
        private readonly ListAutomationRunService $runs,
        private readonly DefinitiveListService $lists,
    ) {}

    public function run(Contest $contest, User $actor): ListAutomationRun
    {
        $validation = $this->validator->definitive($contest);

        if ($validation['errors'] !== [] || $validation['provisional'] === null) {
            throw ValidationException::withMessages(['contest_id' => implode(' ', $validation['errors'])]);
        }

        $provisional = $validation['provisional'];
        $list = $this->lists->generateFromProvisional($provisional, [
            'title' => 'Lista definitiva automática — '.$contest->title,
            'description' => 'A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.',
            'anonymization_mode' => $provisional->anonymization_mode,
            'public_visibility' => false,
        ], $actor);

        return $this->runs->create($contest, ListAutomationType::Definitive, $actor, [
            'status' => ListAutomationStatus::Generated,
            'source_ranking_snapshot_id' => $provisional->ranking_snapshot_id,
            'source_provisional_list_id' => $provisional->id,
            'source_definitive_list_id' => $list->id,
            'total_candidates' => $list->entries()->count(),
            'included_count' => $list->entries()->count(),
            'excluded_count' => 0,
            'warnings' => $validation['warnings'],
            'criteria_snapshot' => [
                'provisional_list_id' => $provisional->id,
                'copy' => 'A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.',
            ],
            'result_payload' => [
                'definitive_list_id' => $list->id,
                'list_number' => $list->list_number,
            ],
        ]);
    }
}
