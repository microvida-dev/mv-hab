<?php

namespace App\Services\ProcedureMinutes;

use App\Models\Application;
use App\Models\Contest;

class ProcedureMinutePayloadBuilder
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function build(array $data): array
    {
        $contest = isset($data['contest_id']) ? Contest::query()->withCount(['applications', 'provisionalLists', 'definitiveLists'])->find($data['contest_id']) : null;
        $application = isset($data['application_id']) ? Application::query()->with(['contest', 'user'])->find($data['application_id']) : null;

        return [
            'copy' => 'A ata foi preparada automaticamente a partir dos dados do procedimento e deve ser revista, validada e aprovada pelos responsáveis competentes.',
            'generated_at' => now()->toDateTimeString(),
            'contest' => $contest ? [
                'id' => $contest->id,
                'code' => $contest->code,
                'title' => $contest->title,
                'applications_count' => $contest->applications_count,
                'provisional_lists_count' => $contest->provisional_lists_count,
                'definitive_lists_count' => $contest->definitive_lists_count,
            ] : null,
            'application' => $application ? [
                'id' => $application->id,
                'number' => $application->application_number,
                'status' => $application->status?->value,
            ] : null,
        ];
    }
}
