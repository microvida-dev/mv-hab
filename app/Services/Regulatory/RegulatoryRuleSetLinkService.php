<?php

namespace App\Services\Regulatory;

use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Validation\ValidationException;

class RegulatoryRuleSetLinkService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function link(array $data, User $actor): array
    {
        $program = null;
        $contest = null;

        if (filled($data['contest_id'] ?? null)) {
            $contest = $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->with(['program.regulatoryProfile', 'regulatoryProfile'])
                ->findOrFail((int) $data['contest_id']);
            $program = $contest->program;
        }

        if (filled($data['program_id'] ?? null)) {
            $selectedProgram = $this->municipalScope
                ->programs(Program::query(), $actor)
                ->with('regulatoryProfile')
                ->findOrFail((int) $data['program_id']);

            if ($program instanceof Program && ! $program->is($selectedProgram)) {
                throw ValidationException::withMessages([
                    'program_id' => 'O programa indicado não corresponde ao concurso selecionado.',
                ]);
            }

            $program = $selectedProgram;
        }

        if (! $program instanceof Program) {
            throw ValidationException::withMessages([
                'program_id' => 'Selecione um programa ou concurso válido.',
            ]);
        }

        $profile = $contest instanceof Contest
            ? ($contest->regulatoryProfile ?? $program->regulatoryProfile)
            : $program->regulatoryProfile;
        $data['program_id'] = $program->id;
        $data['contest_id'] = $contest?->id;
        $data['regulatory_profile_id'] = $profile instanceof AffordableRentRegulatoryProfile
            ? $profile->id
            : null;

        return $data;
    }
}
