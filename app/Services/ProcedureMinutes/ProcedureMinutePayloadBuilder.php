<?php

namespace App\Services\ProcedureMinutes;

use App\Models\Application;
use App\Models\Contest;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProcedureMinutePayloadBuilder
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function build(array $data, ?User $actor = null): array
    {
        $application = $this->resolveApplication($data);
        $contest = $this->resolveContest($data, $application);

        $this->loadProcedureRelations($contest, $application);

        /** @var Collection<int, Application> $applications */
        $applications = $contest instanceof Contest
            ? $contest->applications
            : collect($application instanceof Application ? [$application] : []);

        $housingUnits = $this->buildHousingUnits($contest);
        $applicationPayload = $this->buildApplications($applications);
        $provisionalLists = $this->buildProvisionalLists($contest);
        $definitiveLists = $this->buildDefinitiveLists($contest);
        $hearings = $this->buildHearings($applications, $contest);
        $complaints = $this->buildComplaints($applications);
        $lotteryDraws = $this->buildLotteryDraws($contest);
        $withdrawals = $this->buildWithdrawals($applications);
        $administrativeDecisions = $this->buildAdministrativeDecisions($applications);

        return [
            'copy' => 'A ata foi preparada automaticamente a partir dos dados do procedimento e deve ser revista, validada e aprovada pelos responsáveis competentes.',
            'generated_at' => now()->toDateTimeString(),
            'generated_by' => [
                'id' => $actor?->id,
                'name' => $actor?->name,
            ],
            'municipal' => $this->buildMunicipal($data),
            'meeting' => $this->buildMeeting($data),
            'ata' => $this->buildAtaFields($data, $applications),
            'manual_fields' => $this->buildManualFields($data),
            'program' => $this->buildProgram($contest instanceof Contest ? $contest->program : ($application instanceof Application ? $application->program : null)),
            'contest' => $this->buildContest($contest),
            'application' => $this->buildSelectedApplication($application, $applicationPayload),
            'deadlines' => $this->buildDeadlines($contest),
            'jury' => $this->buildJury($contest),
            'housing_units' => $housingUnits,
            'applications' => $applicationPayload,
            'provisional_lists' => $provisionalLists,
            'definitive_lists' => $definitiveLists,
            'hearings' => $hearings,
            'complaints' => $complaints,
            'lottery_draws' => $lotteryDraws,
            'withdrawals' => $withdrawals,
            'administrative_decisions' => $administrativeDecisions,
            'summary' => [
                'applications_total' => count($applicationPayload),
                'unique_candidates_total' => $applications
                    ->pluck('user_id')
                    ->filter()
                    ->unique()
                    ->count(),
                'housing_units_total' => count($housingUnits),
                'provisional_lists_total' => count($provisionalLists),
                'definitive_lists_total' => count($definitiveLists),
                'hearings_total' => count($hearings),
                'complaints_total' => count($complaints),
                'lottery_draws_total' => count($lotteryDraws),
                'withdrawals_total' => count($withdrawals),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveApplication(array $data): ?Application
    {
        if (empty($data['application_id'])) {
            return null;
        }

        return Application::query()
            ->with([
                'user',
                'program',
                'contest',
                'preferences.housingUnit',
                'applicationScores.details',
                'provisionalListEntries',
                'definitiveListEntries',
                'hearings.submissions',
                'hearingSubmissions',
                'complaints.decision',
                'controlledWithdrawals',
                'processConfirmations',
                'administrativeDecisions',
            ])
            ->find((int) $data['application_id']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveContest(array $data, ?Application $application): ?Contest
    {
        if (! empty($data['contest_id'])) {
            return Contest::query()->find((int) $data['contest_id']);
        }

        return $application?->contest;
    }

    private function loadProcedureRelations(?Contest $contest, ?Application $application): void
    {
        $contest?->loadMissing([
            'program',
            'deadlines',
            'juryMembers.user',
            'contestHousingUnits.housingUnit',
            'applications.user',
            'applications.program',
            'applications.contest',
            'applications.preferences.housingUnit',
            'applications.applicationScores.details',
            'applications.provisionalListEntries',
            'applications.definitiveListEntries',
            'applications.hearings.submissions',
            'applications.hearings.candidate',
            'applications.hearings.application.user',
            'applications.hearingSubmissions',
            'applications.complaints.decision',
            'applications.complaints.candidate',
            'applications.complaints.application.user',
            'applications.controlledWithdrawals',
            'applications.processConfirmations',
            'applications.administrativeDecisions',
            'provisionalLists.entries.candidate',
            'provisionalLists.entries.application',
            'provisionalLists.publications',
            'provisionalLists.hearings.submissions',
            'provisionalLists.hearings.candidate',
            'provisionalLists.hearings.application.user',
            'definitiveLists.entries.candidate',
            'definitiveLists.entries.application',
            'definitiveLists.publications',
            'lotteryDraws.participants.candidate',
            'lotteryDraws.participants.application',
            'lotteryDraws.results.candidate',
            'lotteryDraws.results.application',
            'lotteryDraws.results.assignedHousingUnit',
            'lotteryDraws.convocations',
            'lotteryDraws.attendances',
            'lotteryDraws.postDrawReports',
        ]);

        $application?->loadMissing([
            'user',
            'program',
            'contest',
            'preferences.housingUnit',
            'applicationScores.details',
            'provisionalListEntries',
            'definitiveListEntries',
            'hearings.submissions',
            'hearings.candidate',
            'hearings.application.user',
            'hearingSubmissions',
            'complaints.decision',
            'complaints.candidate',
            'complaints.application.user',
            'controlledWithdrawals',
            'processConfirmations',
            'administrativeDecisions',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildMunicipal(array $data): array
    {
        return [
            'municipality_name' => 'Município de Alcanena',
            'department' => 'Divisão de Desenvolvimento Humano e Social',
            'registry_number' => $this->nullableString($data['municipal_registry_number'] ?? null),
            'process_number' => $this->nullableString($data['municipal_process_number'] ?? null),
            'external_reference' => $this->nullableString($data['external_reference'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildMeeting(array $data): array
    {
        return [
            'date' => $this->nullableString($data['meeting_date'] ?? null),
            'time' => $this->nullableString($data['meeting_time'] ?? null),
            'location' => $this->nullableString($data['meeting_location'] ?? null),
            'subject' => $this->nullableString($data['subject'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, Application>  $applications
     * @return array<string, mixed>
     */
    private function buildAtaFields(array $data, Collection $applications): array
    {
        return [
            'minute_sequence' => $this->nullableString($data['minute_sequence'] ?? '1'),
            'meeting_date_long' => $this->nullableString($data['meeting_date_long'] ?? null),
            'meeting_time_long' => $this->nullableString($data['meeting_time_long'] ?? null),
            'jury_appointment_reference' => $this->nullableString($data['jury_appointment_reference'] ?? null),
            'opening_notice_number' => $this->nullableString($data['opening_notice_number'] ?? null),
            'opening_notice_date' => $this->nullableString($data['opening_notice_date'] ?? null),
            'submission_platform_url' => $this->nullableString($data['submission_platform_url'] ?? null),
            'document_completion_deadline' => $this->nullableString($data['document_completion_deadline'] ?? null),
            'exceptional_application_text' => $this->nullableString($data['exceptional_application_text'] ?? null),
            'preference_instruction_text' => $this->nullableString($data['preference_instruction_text'] ?? null),
            'unique_candidates_total' => $applications
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->count(),
            'manual_jury' => [
                'president' => [
                    'name' => $this->nullableString($data['jury_president_name'] ?? null),
                    'role' => $this->nullableString($data['jury_president_role'] ?? null),
                ],
                'vogals' => array_values(array_filter([
                    [
                        'name' => $this->nullableString($data['jury_vogal_1_name'] ?? null),
                        'role' => $this->nullableString($data['jury_vogal_1_role'] ?? null),
                    ],
                    [
                        'name' => $this->nullableString($data['jury_vogal_2_name'] ?? null),
                        'role' => $this->nullableString($data['jury_vogal_2_role'] ?? null),
                    ],
                    [
                        'name' => $this->nullableString($data['jury_vogal_3_name'] ?? null),
                        'role' => $this->nullableString($data['jury_vogal_3_role'] ?? null),
                    ],
                ], fn (array $member): bool => $member['name'] !== null || $member['role'] !== null)),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildManualFields(array $data): array
    {
        return [
            'legal_basis' => $this->nullableString($data['legal_basis'] ?? null),
            'deliberation_text' => $this->nullableString($data['deliberation_text'] ?? null),
            'observations' => $this->nullableString($data['observations'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildProgram(mixed $program): ?array
    {
        if ($program === null) {
            return null;
        }

        return [
            'id' => $program->id,
            'name' => data_get($program, 'name') ?: data_get($program, 'title'),
            'title' => data_get($program, 'title') ?: data_get($program, 'name'),
            'status' => [
                'value' => $this->enumValue($program->status ?? null),
                'label' => $this->enumLabel($program->status ?? null),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildContest(?Contest $contest): ?array
    {
        if (! $contest instanceof Contest) {
            return null;
        }

        return [
            'id' => $contest->id,
            'code' => $contest->code,
            'title' => $contest->title,
            'summary' => $contest->summary,
            'description' => $contest->description,
            'status' => [
                'value' => $this->enumValue($contest->status),
                'label' => $this->enumLabel($contest->status),
            ],
            'opens_at' => $this->dateTime($contest->opens_at),
            'closes_at' => $this->dateTime($contest->closes_at),
            'published_at' => $this->dateTime($contest->published_at),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDeadlines(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return [];
        }

        return $contest->deadlines
            ->map(fn ($deadline): array => [
                'type' => [
                    'value' => $this->enumValue($deadline->type ?? null),
                    'label' => $this->enumLabel($deadline->type ?? null),
                ],
                'label' => $deadline->label,
                'starts_at' => $this->dateTime($deadline->starts_at),
                'ends_at' => $this->dateTime($deadline->ends_at),
                'description' => $deadline->description,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildJury(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return [];
        }

        return $contest->juryMembers
            ->map(fn ($member): array => [
                'role_in_jury' => $member->role_in_jury,
                'appointed_at' => $this->dateTime($member->appointed_at),
                'user' => [
                    'name' => data_get($member, 'user.name'),
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildHousingUnits(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return [];
        }

        return $contest->contestHousingUnits
            ->map(function ($contestHousingUnit): array {
                $housingUnit = $contestHousingUnit->housingUnit;

                return [
                    'contest_housing_unit_id' => $contestHousingUnit->id,
                    'status' => [
                        'value' => $this->enumValue($contestHousingUnit->status ?? null),
                        'label' => $this->enumLabel($contestHousingUnit->status ?? null),
                    ],
                    'availability_starts_at' => $this->dateTime($contestHousingUnit->availability_starts_at),
                    'availability_ends_at' => $this->dateTime($contestHousingUnit->availability_ends_at),
                    'accessible' => (bool) $contestHousingUnit->accessible,
                    'monthly_rent' => $this->money($contestHousingUnit->monthly_rent),
                    'estimated_expenses' => $this->money($contestHousingUnit->estimated_expenses),
                    'housing_unit' => $housingUnit ? [
                        'id' => $housingUnit->id,
                        'code' => $housingUnit->code,
                        'address' => $housingUnit->address,
                        'typology' => $housingUnit->typology,
                        'bedrooms' => $housingUnit->bedrooms,
                        'monthly_rent' => $this->money($housingUnit->monthly_rent),
                        'parish' => $housingUnit->parish,
                        'locality' => $housingUnit->locality,
                        'postal_code' => $housingUnit->postal_code,
                        'floor' => $housingUnit->floor,
                        'gross_area_sqm' => $housingUnit->gross_area_sqm,
                        'usable_area_sqm' => $housingUnit->usable_area_sqm,
                        'energy_rating' => $housingUnit->energy_rating,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array<int, array<string, mixed>>
     */
    private function buildApplications(Collection $applications): array
    {
        return $applications
            ->map(function (Application $application): array {
                $latestScore = $application->applicationScores->sortByDesc('id')->first();

                return [
                    'id' => $application->id,
                    'application_number' => $application->application_number,
                    'candidate_name' => data_get($application, 'user.name'),
                    'status' => [
                        'value' => $this->enumValue($application->status),
                        'label' => $this->enumLabel($application->status),
                    ],
                    'submitted_at' => $this->dateTime($application->submitted_at),
                    'withdrawn_at' => $this->dateTime($application->withdrawn_at),
                    'latest_score' => $latestScore ? [
                        'total_score' => $latestScore->total_score,
                        'rank_position' => $latestScore->rank_position,
                        'status' => [
                            'value' => $this->enumValue($latestScore->status ?? null),
                            'label' => $this->enumLabel($latestScore->status ?? null),
                        ],
                    ] : null,
                    'preferences' => $application->preferences
                        ->map(fn ($preference): array => [
                            'preference_order' => $preference->preference_order,
                            'housing_unit' => [
                                'id' => $preference->housingUnit?->id,
                                'code' => $preference->housingUnit?->code,
                            ],
                        ])
                        ->values()
                        ->all(),
                    'process_confirmations' => $application->processConfirmations
                        ->map(fn ($confirmation): array => [
                            'process_number' => $confirmation->process_number,
                            'confirmation_number' => $confirmation->confirmation_number,
                            'status' => [
                                'value' => $this->enumValue($confirmation->status ?? null),
                                'label' => $this->enumLabel($confirmation->status ?? null),
                            ],
                        ])
                        ->values()
                        ->all(),
                    'provisional_list_positions' => $application->provisionalListEntries
                        ->map(fn ($entry): array => [
                            'rank_position' => $entry->rank_position,
                            'total_score' => $entry->total_score,
                            'status' => [
                                'value' => $this->enumValue($entry->status ?? null),
                                'label' => $this->enumLabel($entry->status ?? null),
                            ],
                        ])
                        ->values()
                        ->all(),
                    'definitive_list_positions' => $application->definitiveListEntries
                        ->map(fn ($entry): array => [
                            'rank_position' => $entry->rank_position,
                            'total_score' => $entry->total_score,
                            'status' => [
                                'value' => $this->enumValue($entry->status ?? null),
                                'label' => $this->enumLabel($entry->status ?? null),
                            ],
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $applications
     * @return array<string, mixed>|null
     */
    private function buildSelectedApplication(?Application $application, array $applications): ?array
    {
        if (! $application instanceof Application) {
            return null;
        }

        foreach ($applications as $item) {
            if (($item['id'] ?? null) === $application->id) {
                return $item;
            }
        }

        return [
            'id' => $application->id,
            'application_number' => $application->application_number,
            'candidate_name' => data_get($application, 'user.name'),
            'status' => [
                'value' => $this->enumValue($application->status),
                'label' => $this->enumLabel($application->status),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildProvisionalLists(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return [];
        }

        return $contest->provisionalLists
            ->map(fn ($list): array => [
                'list_number' => $list->list_number,
                'status' => [
                    'value' => $this->enumValue($list->status ?? null),
                    'label' => $this->enumLabel($list->status ?? null),
                ],
                'generated_at' => $this->dateTime($list->generated_at),
                'approved_at' => $this->dateTime($list->approved_at),
                'published_at' => $this->dateTime($list->published_at),
                'entries' => $list->entries->map(fn ($entry): array => $this->buildListEntry($entry))->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDefinitiveLists(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return [];
        }

        return $contest->definitiveLists
            ->map(fn ($list): array => [
                'list_number' => $list->list_number,
                'status' => [
                    'value' => $this->enumValue($list->status ?? null),
                    'label' => $this->enumLabel($list->status ?? null),
                ],
                'generated_at' => $this->dateTime($list->generated_at),
                'approved_at' => $this->dateTime($list->approved_at),
                'published_at' => $this->dateTime($list->published_at),
                'entries' => $list->entries->map(fn ($entry): array => $this->buildListEntry($entry))->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildListEntry(mixed $entry): array
    {
        return [
            'application_number' => $entry->application?->application_number,
            'candidate_name' => $entry->candidate?->name,
            'rank_position' => $entry->rank_position,
            'total_score' => $entry->total_score,
            'entry_type' => [
                'value' => $this->enumValue($entry->entry_type ?? null),
                'label' => $this->enumLabel($entry->entry_type ?? null),
            ],
            'status' => [
                'value' => $this->enumValue($entry->status ?? null),
                'label' => $this->enumLabel($entry->status ?? null),
            ],
            'public_identifier' => $entry->public_identifier,
            'metadata' => $entry->metadata,
        ];
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array<int, array<string, mixed>>
     */
    private function buildHearings(Collection $applications, ?Contest $contest): array
    {
        $hearings = $applications->flatMap(fn (Application $application) => $application->hearings);

        if ($contest instanceof Contest) {
            $hearings = $hearings->merge($contest->provisionalLists->flatMap(fn ($list) => $list->hearings));
        }

        return $hearings
            ->unique('id')
            ->map(function ($hearing): array {
                $firstSubmission = $hearing->submissions->first();

                return [
                    'hearing_number' => $hearing->hearing_number,
                    'hearing_type' => [
                        'value' => $this->enumValue($hearing->hearing_type ?? null),
                        'label' => $this->enumLabel($hearing->hearing_type ?? null),
                    ],
                    'subject' => $hearing->subject,
                    'status' => [
                        'value' => $this->enumValue($hearing->status ?? null),
                        'label' => $this->enumLabel($hearing->status ?? null),
                    ],
                    'deadline_at' => $this->dateTime($hearing->deadline_at),
                    'issued_at' => $this->dateTime($hearing->issued_at),
                    'submitted_at' => $this->dateTime($hearing->submitted_at),
                    'reviewed_at' => $this->dateTime($hearing->reviewed_at),
                    'closed_at' => $this->dateTime($hearing->closed_at),
                    'candidate_name' => data_get($hearing, 'candidate.name') ?: data_get($hearing, 'application.user.name'),
                    'application_number' => data_get($hearing, 'application.application_number'),
                    'submissions_count' => $hearing->submissions->count(),
                    'submission_text' => $this->truncate((string) data_get($firstSubmission, 'submission_text', '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array<int, array<string, mixed>>
     */
    private function buildComplaints(Collection $applications): array
    {
        return $applications
            ->flatMap(fn (Application $application) => $application->complaints)
            ->unique('id')
            ->map(fn ($complaint): array => [
                'complaint_number' => $complaint->complaint_number,
                'subject' => $complaint->subject,
                'status' => [
                    'value' => $this->enumValue($complaint->status ?? null),
                    'label' => $this->enumLabel($complaint->status ?? null),
                ],
                'submitted_at' => $this->dateTime($complaint->submitted_at),
                'candidate_name' => data_get($complaint, 'candidate.name') ?: data_get($complaint, 'application.user.name'),
                'application_number' => data_get($complaint, 'application.application_number'),
                'decision' => $complaint->decision ? [
                    'result' => [
                        'value' => $this->enumValue($complaint->decision->decision_result ?? null),
                        'label' => $this->enumLabel($complaint->decision->decision_result ?? null),
                    ],
                    'status' => [
                        'value' => $this->enumValue($complaint->decision->status ?? null),
                        'label' => $this->enumLabel($complaint->decision->status ?? null),
                    ],
                    'summary' => $this->truncate((string) $complaint->decision->summary),
                ] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLotteryDraws(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return [];
        }

        return $contest->lotteryDraws
            ->map(fn ($draw): array => [
                'id' => $draw->id,
                'status' => [
                    'value' => $this->enumValue($draw->status ?? null),
                    'label' => $this->enumLabel($draw->status ?? null),
                ],
                'draw_type' => [
                    'value' => $this->enumValue($draw->draw_type ?? null),
                    'label' => $this->enumLabel($draw->draw_type ?? null),
                ],
                'scheduled_at' => $this->dateTime($draw->scheduled_at),
                'started_at' => $this->dateTime($draw->started_at),
                'completed_at' => $this->dateTime($draw->completed_at),
                'validated_at' => $this->dateTime($draw->validated_at),
                'participants_count' => $draw->participants->count(),
                'results_count' => $draw->results->count(),
                'participants' => $draw->participants->map(fn ($participant): array => [
                    'participant_number' => $participant->participant_number,
                    'candidate_name' => data_get($participant, 'candidate.name'),
                    'application_number' => data_get($participant, 'application.application_number'),
                    'rank_position' => $participant->rank_position,
                    'previous_score' => $participant->previous_score,
                    'status' => [
                        'value' => $this->enumValue($participant->status ?? null),
                        'label' => $this->enumLabel($participant->status ?? null),
                    ],
                ])->values()->all(),
                'results' => $draw->results->map(fn ($result): array => [
                    'draw_order' => $result->draw_order,
                    'candidate_name' => data_get($result, 'candidate.name'),
                    'application_number' => data_get($result, 'application.application_number'),
                    'selected' => (bool) $result->selected,
                    'result_type' => [
                        'value' => $this->enumValue($result->result_type ?? null),
                        'label' => $this->enumLabel($result->result_type ?? null),
                    ],
                    'status' => [
                        'value' => $this->enumValue($result->status ?? null),
                        'label' => $this->enumLabel($result->status ?? null),
                    ],
                    'assigned_housing_unit' => [
                        'code' => data_get($result, 'assignedHousingUnit.code'),
                        'title' => data_get($result, 'assignedHousingUnit.public_title') ?: data_get($result, 'assignedHousingUnit.code'),
                    ],
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array<int, array<string, mixed>>
     */
    private function buildWithdrawals(Collection $applications): array
    {
        return $applications
            ->flatMap(fn (Application $application) => $application->controlledWithdrawals->map(fn ($withdrawal) => [
                'application_number' => $application->application_number,
                'candidate_name' => data_get($application, 'user.name'),
                'status' => [
                    'value' => $this->enumValue($withdrawal->status ?? null),
                    'label' => $this->enumLabel($withdrawal->status ?? null),
                ],
                'reason' => $this->truncate((string) ($withdrawal->reason ?? '')),
                'processed_at' => $this->dateTime($withdrawal->completed_at ?? $withdrawal->confirmed_at ?? null),
                'withdrawn_at' => $this->dateTime($application->withdrawn_at),
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array<int, array<string, mixed>>
     */
    private function buildAdministrativeDecisions(Collection $applications): array
    {
        return $applications
            ->flatMap(fn (Application $application) => $application->administrativeDecisions->map(fn ($decision) => [
                'application_number' => $application->application_number,
                'candidate_name' => data_get($application, 'user.name'),
                'type' => [
                    'value' => $this->enumValue($decision->decision_type ?? null),
                    'label' => $this->enumLabel($decision->decision_type ?? null),
                ],
                'status' => [
                    'value' => $this->enumValue($decision->status ?? null),
                    'label' => $this->enumLabel($decision->status ?? null),
                ],
                'summary' => $this->truncate((string) ($decision->summary ?? '')),
                'grounds' => $this->truncate((string) ($decision->grounds ?? '')),
                'approved_at' => $this->dateTime($decision->approved_at),
            ]))
            ->values()
            ->all();
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }

    private function enumLabel(mixed $value): ?string
    {
        if (is_object($value) && method_exists($value, 'label')) {
            return (string) $value->label();
        }

        return $this->enumValue($value);
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value === null || $value === '' ? null : (string) $value;
    }

    private function money(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function truncate(string $value, int $limit = 600): string
    {
        return Str::limit(trim($value), $limit);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
