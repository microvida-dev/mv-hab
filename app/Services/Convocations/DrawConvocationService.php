<?php

namespace App\Services\Convocations;

use App\Enums\ConvocationStatus;
use App\Enums\LotteryParticipantStatus;
use App\Models\DrawConvocation;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DrawConvocationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, DrawConvocation>
     */
    public function generate(LotteryDraw $draw, array $data, User $actor): Collection
    {
        return DB::transaction(function () use ($draw, $data, $actor): Collection {
            $participants = $draw->participants()
                ->where('is_eligible', true)
                ->whereIn('status', [
                    LotteryParticipantStatus::Included->value,
                    LotteryParticipantStatus::Reserve->value,
                    LotteryParticipantStatus::Winner->value,
                ])
                ->get();

            if ($participants->isEmpty()) {
                throw ValidationException::withMessages(['participants' => 'Não existem participantes convocáveis.']);
            }

            foreach ($participants as $participant) {
                /** @var LotteryParticipant $participant */
                $exists = DrawConvocation::query()
                    ->where('lottery_run_id', $draw->id)
                    ->where('application_id', $participant->application_id)
                    ->whereNotIn('status', [ConvocationStatus::Cancelled->value, ConvocationStatus::Expired->value])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $convocation = new DrawConvocation([
                    'lottery_run_id' => $draw->id,
                    'contest_id' => $draw->contest_id,
                    'application_id' => $participant->application_id,
                    'user_id' => $participant->user_id,
                    'lottery_participant_id' => $participant->id,
                    'scheduled_for' => $data['scheduled_for'],
                    'location' => $data['location'],
                    'instructions' => $data['instructions'] ?? 'A convocatória indica a data, hora, local e instruções do ato. A falta de comparência pode produzir efeitos no procedimento, nos termos aplicáveis ao concurso.',
                    'metadata' => ['source' => 'sprint_25_auto_generation'],
                ]);

                $convocation->forceFill([
                    'status' => ConvocationStatus::Generated,
                    'generated_at' => now(),
                    'generated_by' => $actor->id,
                ])->save();

                $participant->forceFill([
                    'status' => LotteryParticipantStatus::Notified,
                    'notified_at' => now(),
                ])->save();
            }

            $this->audit->record(AuditEvents::CREATE, $draw, 'communications', 'draw_convocations_generate', 'Convocatórias do sorteio geradas.');

            return $draw->convocations()->with('candidate')->latest()->get();
        });
    }

    public function send(DrawConvocation $convocation, User $actor): DrawConvocation
    {
        $convocation->forceFill([
            'status' => ConvocationStatus::Sent,
            'sent_at' => now(),
            'sent_by' => $actor->id,
        ])->save();

        $this->audit->record(AuditEvents::UPDATE, $convocation, 'communications', 'draw_convocation_send', 'Convocatória marcada como enviada.');

        return $convocation->refresh();
    }

    public function markRead(DrawConvocation $convocation, User $candidate): DrawConvocation
    {
        if ($convocation->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['convocation' => 'Convocatória indisponível para este candidato.']);
        }

        $convocation->forceFill([
            'status' => ConvocationStatus::Read,
            'read_at' => now(),
        ])->save();

        return $convocation->refresh();
    }
}
