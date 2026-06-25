<?php

namespace Database\Seeders;

use App\Enums\MessageVisibility;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\ContextualFaqCategory;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\HousingVisitStatusHistory;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskCreationService;
use Illuminate\Database\Seeder;

class CandidateSupportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::query()->where('email', 'tecnico-demo@exemplo.pt')->first()
            ?? User::query()->where('email', 'tecnico@demo.test')->first()
            ?? User::query()->first();
        $candidate = User::query()->where('email', 'candidato-demo@exemplo.pt')->first()
            ?? User::query()->where('email', 'candidato@demo.test')->first()
            ?? User::query()->latest()->first();
        $contest = Contest::query()->latest()->first();

        $category = ContextualFaqCategory::query()->firstOrCreate(
            ['code' => 'candidaturas-visitas'],
            [
                'name' => 'Candidaturas e visitas',
                'description' => 'Ajuda contextual fictícia para validação local.',
                'sort_order' => 10,
                'is_active' => true,
            ],
        );

        $faq = ContextualFaq::query()
            ->where('context_key', 'application')
            ->where('question', 'Como posso pedir apoio sobre a minha candidatura?')
            ->first();

        if (! $faq instanceof ContextualFaq) {
            $faq = new ContextualFaq([
                'contextual_faq_category_id' => $category->id,
                'contest_id' => $contest?->id,
                'context_key' => 'application',
                'question' => 'Como posso pedir apoio sobre a minha candidatura?',
                'answer' => 'Use a área Apoio para abrir um ticket. Os serviços municipais respondem na plataforma.',
                'keywords' => ['candidatura', 'apoio', 'ticket'],
                'sort_order' => 10,
                'is_active' => true,
                'published_at' => now(),
            ]);
            $faq->forceFill([
                'created_by' => $staff?->id,
                'updated_by' => $staff?->id,
            ])->save();
        }

        $availability = VisitAvailability::query()->where('title', 'Visitas de demonstração')->first();
        if (! $availability instanceof VisitAvailability) {
            $availability = new VisitAvailability([
                'contest_id' => $contest?->id,
                'staff_user_id' => $staff?->id,
                'description' => 'Disponibilidade fictícia para validação local da Sprint 22.',
                'starts_at' => now()->addDays(5)->setTime(9, 0),
                'ends_at' => now()->addDays(5)->setTime(12, 0),
                'slot_duration_minutes' => 30,
                'capacity_per_slot' => 2,
                'buffer_minutes' => 0,
                'timezone' => config('app.timezone', 'UTC'),
                'is_active' => true,
            ]);
            $availability->forceFill([
                'title' => 'Visitas de demonstração',
                'created_by' => $staff?->id,
                'updated_by' => $staff?->id,
            ])->save();
        }

        for ($minute = 0; $minute < 180; $minute += 30) {
            $startsAt = now()->addDays(5)->setTime(9, 0)->addMinutes($minute);
            $endsAt = (clone $startsAt)->addMinutes(30);
            $slot = VisitSlot::query()
                ->where('visit_availability_id', $availability->id)
                ->where('starts_at', $startsAt)
                ->where('ends_at', $endsAt)
                ->first();

            if (! $slot instanceof VisitSlot) {
                $slot = new VisitSlot([
                    'visit_availability_id' => $availability->id,
                    'contest_id' => $contest?->id,
                    'staff_user_id' => $staff?->id,
                    'capacity' => 2,
                    'location' => 'Edifício municipal',
                    'meeting_point' => 'Entrada principal',
                    'notes' => 'Slot fictício para testes locais.',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);
                $slot->forceFill([
                    'status' => VisitSlotStatus::Available,
                    'booked_count' => 0,
                ])->save();
            }
        }

        if ($candidate instanceof User) {
            $ticket = SupportTicket::query()->where('ticket_number', 'SUP-DEMO-2026-000001')->first();
            if (! $ticket instanceof SupportTicket) {
                $ticket = new SupportTicket([
                    'subject' => 'Pedido fictício de apoio',
                    'description' => 'Pedido criado apenas para validação local do módulo de apoio.',
                ]);
                $ticket->forceFill([
                    'ticket_number' => 'SUP-DEMO-2026-000001',
                    'user_id' => $candidate->id,
                    'contest_id' => $contest?->id,
                    'assigned_to' => $staff?->id,
                    'category' => TicketCategory::Application,
                    'priority' => TicketPriority::Normal,
                    'status' => TicketStatus::Open,
                    'context' => ['source' => 'CandidateSupportDemoSeeder'],
                    'last_message_at' => now(),
                ])->save();
            }

            SupportTicketMessage::query()->firstOrCreate(
                ['support_ticket_id' => $ticket->id, 'message' => 'Pedido criado apenas para validação local do módulo de apoio.'],
                [
                    'sender_user_id' => $candidate->id,
                    'visibility' => MessageVisibility::CandidateVisible,
                ],
            );

            app(WorkTaskCreationService::class)->createFromSource(
                type: WorkTask::TYPE_SUPPORT_TICKET,
                related: $ticket,
                actor: $candidate,
                source: 'support_ticket:'.$ticket->id,
                priority: WorkTask::PRIORITY_NORMAL,
                metadata: [
                    'support_ticket_id' => $ticket->id,
                    'category' => TicketCategory::Application->value,
                    'contest_id' => $contest?->id,
                    'channel' => 'demo_seed',
                ],
            );

            $slot = VisitSlot::query()
                ->where('visit_availability_id', $availability->id)
                ->oldest('starts_at')
                ->first();
            $housingUnit = HousingUnit::query()->where('code', 'ALC-DEMO-T2-MONSANTO')->first()
                ?? HousingUnit::query()->first();

            if ($slot instanceof VisitSlot && $housingUnit instanceof HousingUnit) {
                $visit = HousingVisit::query()->where('visit_number', 'VIS-DEMO-2026-000001')->first();

                if (! $visit instanceof HousingVisit) {
                    $visit = new HousingVisit([
                        'candidate_notes' => 'Pedido fictício de visita para validação municipal controlada.',
                    ]);
                    $visit->forceFill([
                        'visit_number' => 'VIS-DEMO-2026-000001',
                        'visit_slot_id' => $slot->id,
                        'contest_id' => $contest?->id,
                        'housing_unit_id' => $housingUnit->id,
                        'candidate_user_id' => $candidate->id,
                        'staff_user_id' => $staff?->id,
                        'status' => VisitStatus::PendingConfirmation,
                        'scheduled_at' => now(),
                        'starts_at' => $slot->starts_at,
                        'ends_at' => $slot->ends_at,
                        'location' => 'Edifício municipal',
                        'meeting_point' => 'Entrada principal',
                    ])->save();
                }

                HousingVisitStatusHistory::query()->firstOrCreate(
                    [
                        'housing_visit_id' => $visit->id,
                        'to_status' => VisitStatus::PendingConfirmation->value,
                    ],
                    [
                        'from_status' => null,
                        'changed_by' => $candidate->id,
                        'reason' => 'seed_demo',
                        'notes' => 'Histórico fictício de visita para demonstração.',
                        'changed_at' => now(),
                        'created_at' => now(),
                    ],
                );

                app(WorkTaskCreationService::class)->createFromSource(
                    type: WorkTask::TYPE_VISIT_SCHEDULE,
                    related: $visit,
                    actor: $candidate,
                    source: 'housing_visit:'.$visit->id,
                    priority: WorkTask::PRIORITY_NORMAL,
                    metadata: [
                        'visit_id' => $visit->id,
                        'contest_id' => $contest?->id,
                        'housing_unit_id' => $housingUnit->id,
                        'status' => VisitStatus::PendingConfirmation->value,
                        'channel' => 'demo_seed',
                    ],
                );
            }
        }
    }
}
