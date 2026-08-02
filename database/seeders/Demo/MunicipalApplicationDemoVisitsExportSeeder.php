<?php

namespace Database\Seeders\Demo;

use App\Enums\ApplicationReportStatus;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentDossierItemStatus;
use App\Enums\DocumentDossierStatus;
use App\Enums\ReportFormat;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\DocumentDossier;
use App\Models\DocumentDossierItem;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\HousingPreference;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\DocumentStandardization\DocumentDossierService;
use App\Services\OperationalReports\ApplicationReportService;
use App\Services\Visits\VisitAvailabilityService;
use App\Services\Visits\VisitBookingService;
use App\Services\Visits\VisitReschedulingService;
use App\Services\Visits\VisitSlotGenerationService;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use LogicException;

final class MunicipalApplicationDemoVisitsExportSeeder extends Seeder
{
    public const VISIT_CANDIDATE_NOTES =
        'Pretendo conhecer a primeira habitação T2 da demonstração.';

    public const VISIT_STAFF_NOTES =
        'Visita fictícia concluída sem efeitos administrativos.';

    public const RESCHEDULE_REASON =
        'Reagendamento fictício solicitado para demonstração do fluxo.';

    public const AVAILABILITY_TITLE =
        'Visitas ao fogo T2 municipal — demonstração';

    public const LOCATION =
        'Habitação municipal de demonstração — Alcanena';

    public const MEETING_POINT =
        'Entrada principal do imóvel';

    private const EXPECTED_SLOT_COUNT = 4;

    private const EXPECTED_DOCUMENT_COUNT = 15;

    public function run(): void
    {
        $context = app(MunicipalApplicationDemoContext::class);
        $context->assertSeederAllowed();

        $candidate = $this->candidate();
        $visitManager = $this->visitManager();
        $exporter = $this->exporter();
        $application = $this->application($candidate);
        $housingUnit = $this->housingUnit($application);

        $this->assertPrerequisites(
            $application,
            $visitManager,
            $exporter,
        );

        if ($this->isFinalState($application, $housingUnit)) {
            $this->assertFinalState(
                $application,
                $housingUnit,
                $visitManager,
                $exporter,
            );

            return;
        }

        $availability = $this->ensureAvailability(
            $application,
            $housingUnit,
            $visitManager,
            $context->referenceDate(),
        );

        $slots = $this->ensureSlots(
            $availability,
            $visitManager,
        );

        $this->ensureVisitLifecycle(
            $application,
            $housingUnit,
            $candidate,
            $visitManager,
            $slots,
        );

        $this->ensureApplicationReport(
            $application,
            $exporter,
            ReportFormat::Html,
        );
        $this->ensureApplicationReport(
            $application,
            $exporter,
            ReportFormat::Csv,
        );
        $this->ensureDocumentDossier(
            $application,
            $exporter,
        );

        $this->assertFinalState(
            $application,
            $housingUnit,
            $visitManager,
            $exporter,
        );
    }

    private function candidate(): User
    {
        return $this->demoUser(
            MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
        );
    }

    private function visitManager(): User
    {
        return $this->demoUser(
            MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_EMAIL,
        );
    }

    private function exporter(): User
    {
        return $this->demoUser(
            MunicipalApplicationDemoAccessSeeder::EXPORTER_EMAIL,
        );
    }

    private function demoUser(string $email): User
    {
        return User::query()
            ->where('email', $email)
            ->whereHas(
                'municipality',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
                ),
            )
            ->sole();
    }

    private function application(User $candidate): Application
    {
        return Application::query()
            ->where('user_id', $candidate->id)
            ->whereHas(
                'contest',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
                ),
            )
            ->with([
                'contest.program',
                'housingPreferences.housingUnit',
                'documentSubmissions.versions',
            ])
            ->sole();
    }

    private function housingUnit(
        Application $application,
    ): HousingUnit {
        $preference = $application->housingPreferences
            ->sortBy('preference_order')
            ->first();

        if (! $preference instanceof HousingPreference) {
            throw new LogicException(
                'A candidatura demo não possui preferências habitacionais.',
            );
        }

        $housingUnit = $preference->housingUnit;

        if (! $housingUnit instanceof HousingUnit) {
            throw new LogicException(
                'A preferência principal demo não possui habitação.',
            );
        }

        return $housingUnit;
    }

    private function assertPrerequisites(
        Application $application,
        User $visitManager,
        User $exporter,
    ): void {
        if ($application->status !== ApplicationStatus::Submitted) {
            throw new LogicException(
                'O lote de visitas e exportação exige candidatura submetida.',
            );
        }

        if (
            ! $visitManager->hasPermission('visits.create')
            || ! $visitManager->hasPermission(
                'visits.availabilities.generate_slots',
            )
            || ! $visitManager->hasPermission('visits.confirm')
            || ! $visitManager->hasPermission('visits.complete')
        ) {
            throw new LogicException(
                'O gestor de visitas demo não possui as permissões mínimas.',
            );
        }

        if (
            ! $exporter->hasPermission('applications.export')
            || ! $exporter->hasPermission('reports.export')
            || ! $exporter->hasPermission('reports.audit')
        ) {
            throw new LogicException(
                'O exportador demo não possui as permissões mínimas.',
            );
        }

        $submissions = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->with('versions')
            ->get();

        if (
            $submissions->count() !== self::EXPECTED_DOCUMENT_COUNT
            || $submissions->contains(
                static fn (
                    DocumentSubmission $submission,
                ): bool => $submission->getRawOriginal('status')
                    !== 'validated',
            )
            || $submissions->sum(
                static fn (
                    DocumentSubmission $submission,
                ): int => $submission->versions->count(),
            ) !== 16
        ) {
            throw new LogicException(
                'A exportação demo exige 15 documentos validados e '
                .'16 versões preservadas.',
            );
        }
    }

    private function ensureAvailability(
        Application $application,
        HousingUnit $housingUnit,
        User $visitManager,
        CarbonImmutable $referenceDate,
    ): VisitAvailability {
        $startsAt = $referenceDate
            ->addDays(14)
            ->setTime(9, 0);
        $endsAt = $startsAt->addHours(2);

        $availabilities = VisitAvailability::withTrashed()
            ->where('contest_id', $application->contest_id)
            ->where('housing_unit_id', $housingUnit->id)
            ->where('staff_user_id', $visitManager->id)
            ->where('starts_at', $startsAt)
            ->where('ends_at', $endsAt)
            ->get();

        if ($availabilities->count() > 1) {
            throw new LogicException(
                'Existem disponibilidades demo duplicadas.',
            );
        }

        $availability = $availabilities->first();

        if ($availability?->trashed()) {
            throw new LogicException(
                'A disponibilidade demo encontra-se eliminada.',
            );
        }

        if (! $availability instanceof VisitAvailability) {
            $availability = app(VisitAvailabilityService::class)
                ->store(
                    [
                        'title' => self::AVAILABILITY_TITLE,
                        'contest_id' => $application->contest_id,
                        'housing_unit_id' => $housingUnit->id,
                        'staff_user_id' => $visitManager->id,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'slot_duration_minutes' => 30,
                        'capacity_per_slot' => 1,
                        'buffer_minutes' => 0,
                        'timezone' => 'Europe/Lisbon',
                        'is_active' => true,
                    ],
                    $visitManager,
                );
        }

        if (
            $availability->title !== self::AVAILABILITY_TITLE
            || (int) $availability->municipality_id
                !== (int) $visitManager->municipality_id
            || (int) $availability->contest_id
                !== (int) $application->contest_id
            || (int) $availability->housing_unit_id
                !== (int) $housingUnit->id
            || (int) $availability->staff_user_id
                !== (int) $visitManager->id
            || (int) $availability->slot_duration_minutes !== 30
            || (int) $availability->capacity_per_slot !== 1
            || (int) $availability->buffer_minutes !== 0
            || ! (bool) $availability->is_active
            || $availability->starts_at?->toIso8601String()
                !== $startsAt->toIso8601String()
            || $availability->ends_at?->toIso8601String()
                !== $endsAt->toIso8601String()
        ) {
            throw new LogicException(
                'A disponibilidade demo possui configuração incompatível.',
            );
        }

        return $availability->refresh();
    }

    /**
     * @return Collection<int, VisitSlot>
     */
    private function ensureSlots(
        VisitAvailability $availability,
        User $visitManager,
    ): Collection {
        $slots = app(VisitSlotGenerationService::class)
            ->generate(
                $availability,
                $visitManager,
                [
                    'location' => self::LOCATION,
                    'meeting_point' => self::MEETING_POINT,
                    'notes' => 'Horários fictícios para demonstração.',
                ],
            )
            ->sortBy('starts_at')
            ->values();

        if ($slots->count() !== self::EXPECTED_SLOT_COUNT) {
            throw new LogicException(
                'A disponibilidade demo deve gerar exatamente quatro slots.',
            );
        }

        foreach ($slots as $index => $slot) {
            if (
                (int) $slot->visit_availability_id
                    !== (int) $availability->id
                || (int) $slot->municipality_id
                    !== (int) $availability->municipality_id
                || (int) $slot->contest_id
                    !== (int) $availability->contest_id
                || (int) $slot->housing_unit_id
                    !== (int) $availability->housing_unit_id
                || (int) $slot->staff_user_id
                    !== (int) $availability->staff_user_id
                || (int) $slot->capacity !== 1
                || $slot->location !== self::LOCATION
                || $slot->meeting_point !== self::MEETING_POINT
                || $slot->starts_at?->toIso8601String()
                    !== $availability->starts_at
                        ?->copy()
                        ->addMinutes($index * 30)
                        ->toIso8601String()
            ) {
                throw new LogicException(
                    'Um slot demo possui contexto ou horário incompatível.',
                );
            }
        }

        return $slots;
    }

    /**
     * @param  Collection<int, VisitSlot>  $slots
     */
    private function ensureVisitLifecycle(
        Application $application,
        HousingUnit $housingUnit,
        User $candidate,
        User $visitManager,
        Collection $slots,
    ): HousingVisit {
        $visits = HousingVisit::withTrashed()
            ->where('application_id', $application->id)
            ->where('candidate_user_id', $candidate->id)
            ->where('housing_unit_id', $housingUnit->id)
            ->get();

        if ($visits->count() > 1) {
            throw new LogicException(
                'Existem visitas demo duplicadas.',
            );
        }

        $visit = $visits->first();

        if ($visit?->trashed()) {
            throw new LogicException(
                'A visita demo encontra-se eliminada.',
            );
        }

        $firstSlot = $slots->get(0);
        $secondSlot = $slots->get(1);

        if (
            ! $firstSlot instanceof VisitSlot
            || ! $secondSlot instanceof VisitSlot
        ) {
            throw new LogicException(
                'Os slots necessários ao fluxo demo não existem.',
            );
        }

        $booking = app(VisitBookingService::class);

        if (! $visit instanceof HousingVisit) {
            $visit = $booking->book(
                $candidate,
                [
                    'visit_slot_id' => $firstSlot->id,
                    'application_id' => $application->id,
                    'contest_id' => $application->contest_id,
                    'housing_unit_id' => $housingUnit->id,
                    'candidate_notes' => self::VISIT_CANDIDATE_NOTES,
                ],
            );
        }

        if ($visit->status === VisitStatus::PendingConfirmation) {
            $visit = $booking->confirm(
                $visit,
                $visitManager,
            );
        }

        if ($visit->status === VisitStatus::Confirmed) {
            $visit = app(VisitReschedulingService::class)
                ->reschedule(
                    $visit,
                    $secondSlot,
                    $candidate,
                    self::RESCHEDULE_REASON,
                );
        }

        if ($visit->status === VisitStatus::Rescheduled) {
            $visit = $booking->complete(
                $visit,
                $visitManager,
                self::VISIT_STAFF_NOTES,
            );
        }

        if ($visit->status !== VisitStatus::Completed) {
            throw new LogicException(
                'A visita demo não terminou no estado concluído.',
            );
        }

        $visit->refresh()->load([
            'slot',
            'statusHistories',
        ]);

        if (
            (int) $visit->municipality_id
                !== (int) $visitManager->municipality_id
            || (int) $visit->application_id
                !== (int) $application->id
            || (int) $visit->contest_id
                !== (int) $application->contest_id
            || (int) $visit->housing_unit_id
                !== (int) $housingUnit->id
            || (int) $visit->candidate_user_id
                !== (int) $candidate->id
            || (int) $visit->staff_user_id
                !== (int) $visitManager->id
            || (int) $visit->visit_slot_id
                !== (int) $secondSlot->id
            || $visit->confirmed_at === null
            || $visit->completed_at === null
            || $visit->candidate_notes
                !== self::VISIT_CANDIDATE_NOTES
            || $visit->staff_notes !== self::VISIT_STAFF_NOTES
            || $visit->statusHistories()->count() !== 4
        ) {
            throw new LogicException(
                'O histórico ou contexto da visita demo é incompatível.',
            );
        }

        $firstSlot->refresh();
        $secondSlot->refresh();

        $firstSlotStatus = (string) $firstSlot->getRawOriginal(
            'status',
        );
        $secondSlotStatus = (string) $secondSlot->getRawOriginal(
            'status',
        );
        $firstBookedCount = (int) $firstSlot->getAttribute(
            'booked_count',
        );
        $secondBookedCount = (int) $secondSlot->getAttribute(
            'booked_count',
        );

        if (
            $firstSlotStatus !== VisitSlotStatus::Available->value
            || $firstBookedCount !== 0
            || $secondSlotStatus !== VisitSlotStatus::Full->value
            || $secondBookedCount !== 1
        ) {
            throw new LogicException(
                'As reservas dos slots demo não estão coerentes.',
            );
        }

        return $visit;
    }

    private function ensureApplicationReport(
        Application $application,
        User $exporter,
        ReportFormat $format,
    ): ApplicationReport {
        $reports = ApplicationReport::withTrashed()
            ->where('application_id', $application->id)
            ->where('format', $format->value)
            ->get();

        if ($reports->count() > 1) {
            throw new LogicException(
                'Existem relatórios demo duplicados no formato '
                ."{$format->value}.",
            );
        }

        $report = $reports->first();

        if (
            $report instanceof ApplicationReport
            && $report->trashed()
        ) {
            throw new LogicException(
                'Um relatório demo encontra-se eliminado.',
            );
        }

        if (! $report instanceof ApplicationReport) {
            $report = app(ApplicationReportService::class)
                ->generate(
                    $application,
                    $exporter,
                    [
                        'format' => $format->value,
                        'include_timeline' => true,
                    ],
                );
        }

        $report->refresh();

        $reportStatus = (string) $report->getRawOriginal(
            'status',
        );
        $reportFormat = (string) $report->getRawOriginal(
            'format',
        );

        if (
            $reportStatus
                !== ApplicationReportStatus::Generated->value
            || (int) $report->getAttribute('application_id')
                !== (int) $application->id
            || (int) $report->getAttribute('contest_id')
                !== (int) $application->contest_id
            || (int) $report->getAttribute('user_id')
                !== (int) $application->user_id
            || (int) $report->getAttribute('generated_by')
                !== (int) $exporter->id
            || $reportFormat !== $format->value
        ) {
            throw new LogicException(
                'O relatório de candidatura demo possui contexto '
                .'incompatível.',
            );
        }

        $payload = $report->getAttribute('payload');

        if (
            ! is_array($payload)
            || data_get(
                $payload,
                'application.application_number',
            ) !== $application->application_number
        ) {
            throw new LogicException(
                'O payload do relatório demo é incompatível.',
            );
        }

        $filePath = $report->getAttribute('file_path');
        $expectedExtension = $format->storageExtension();

        if (
            ! is_string($filePath)
            || ! str_ends_with(
                $filePath,
                '.'.$expectedExtension,
            )
            || ! Storage::disk('local')->exists($filePath)
        ) {
            throw new LogicException(
                'O ficheiro do relatório demo é incompatível.',
            );
        }

        $contents = Storage::disk('local')->get($filePath);

        if (
            ! is_string($contents)
            || ! str_contains(
                $contents,
                (string) $application->application_number,
            )
        ) {
            throw new LogicException(
                'O ficheiro do relatório demo não contém a candidatura.',
            );
        }

        return $report;
    }

    private function ensureDocumentDossier(
        Application $application,
        User $exporter,
    ): DocumentDossier {
        $dossiers = DocumentDossier::withTrashed()
            ->where('application_id', $application->id)
            ->get();

        if ($dossiers->count() > 1) {
            throw new LogicException(
                'Existem dossiers documentais demo duplicados.',
            );
        }

        $dossier = $dossiers->first();

        if (
            $dossier instanceof DocumentDossier
            && $dossier->trashed()
        ) {
            throw new LogicException(
                'O dossier documental demo encontra-se eliminado.',
            );
        }

        if (! $dossier instanceof DocumentDossier) {
            $dossier = app(DocumentDossierService::class)
                ->generate(
                    $application,
                    $exporter,
                    ['required_only' => true],
                );
        }

        $dossier->refresh();
        $dossier->load('items');

        $dossierStatus = (string) $dossier->getRawOriginal(
            'status',
        );

        if (
            $dossierStatus
                !== DocumentDossierStatus::Standardized->value
            || (int) $dossier->getAttribute('application_id')
                !== (int) $application->id
            || (int) $dossier->getAttribute('contest_id')
                !== (int) $application->contest_id
            || (int) $dossier->getAttribute('user_id')
                !== (int) $application->user_id
            || (int) $dossier->getAttribute('created_by')
                !== (int) $exporter->id
            || $dossier->items->count()
                !== self::EXPECTED_DOCUMENT_COUNT
            || (int) $dossier->getAttribute(
                'missing_documents_count',
            ) !== 0
            || (int) $dossier->getAttribute(
                'rejected_documents_count',
            ) !== 0
            || (int) $dossier->getAttribute(
                'expired_documents_count',
            ) !== 0
            || (int) $dossier->getAttribute(
                'validated_documents_count',
            ) !== self::EXPECTED_DOCUMENT_COUNT
            || $dossier->items->contains(
                static function (
                    DocumentDossierItem $item,
                ): bool {
                    return (string) $item->getRawOriginal(
                        'status',
                    ) !== DocumentDossierItemStatus::Validated->value
                        || ! (bool) $item->getAttribute(
                            'is_validated',
                        )
                        || (bool) $item->getAttribute(
                            'is_missing',
                        )
                        || (bool) $item->getAttribute(
                            'is_rejected',
                        )
                        || (bool) $item->getAttribute(
                            'is_expired',
                        );
                },
            )
        ) {
            throw new LogicException(
                'O dossier documental demo possui conteúdo incompatível.',
            );
        }

        $filePath = $dossier->getAttribute('file_path');

        if (
            ! is_string($filePath)
            || ! Storage::disk('local')->exists($filePath)
        ) {
            throw new LogicException(
                'O ficheiro do dossier documental demo é incompatível.',
            );
        }

        $submissionIds = $dossier->items
            ->pluck('document_submission_id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $expectedSubmissionIds = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($submissionIds !== $expectedSubmissionIds) {
            throw new LogicException(
                'O dossier demo não referencia os 15 documentos atuais.',
            );
        }

        $contents = Storage::disk('local')->get($filePath);

        $standardizationPayload = $dossier->getAttribute(
            'standardization_payload',
        );
        $housingPreferences = is_array($standardizationPayload)
            ? ($standardizationPayload['housing_preferences'] ?? [])
            : [];

        if (! is_array($housingPreferences)) {
            $housingPreferences = [];
        }

        $preferenceRowCount = count(
            array_filter(
                $housingPreferences,
                static fn (mixed $preference): bool => is_array($preference),
            ),
        );

        $expectedTableRowCount = 2
            + $preferenceRowCount
            + $dossier->items->count();

        if (
            ! is_string($contents)
            || ! str_contains(
                $contents,
                (string) $application->application_number,
            )
            || substr_count($contents, '<tr>')
                !== $expectedTableRowCount
        ) {
            throw new LogicException(
                'O índice HTML do dossier demo está incompleto.',
            );
        }

        return $dossier;
    }

    private function isFinalState(
        Application $application,
        HousingUnit $housingUnit,
    ): bool {
        return HousingVisit::query()
            ->where('application_id', $application->id)
            ->where('housing_unit_id', $housingUnit->id)
            ->where('status', VisitStatus::Completed->value)
            ->count() === 1
            && VisitAvailability::query()
                ->where('contest_id', $application->contest_id)
                ->where('housing_unit_id', $housingUnit->id)
                ->count() === 1
            && VisitSlot::query()
                ->whereHas(
                    'availability',
                    static fn ($query) => $query
                        ->where(
                            'contest_id',
                            $application->contest_id,
                        )
                        ->where(
                            'housing_unit_id',
                            $housingUnit->id,
                        ),
                )
                ->count() === self::EXPECTED_SLOT_COUNT
            && ApplicationReport::query()
                ->where('application_id', $application->id)
                ->whereIn('format', [
                    ReportFormat::Html->value,
                    ReportFormat::Csv->value,
                ])
                ->count() === 2
            && DocumentDossier::query()
                ->where('application_id', $application->id)
                ->where(
                    'status',
                    DocumentDossierStatus::Standardized->value,
                )
                ->count() === 1;
    }

    private function assertFinalState(
        Application $application,
        HousingUnit $housingUnit,
        User $visitManager,
        User $exporter,
    ): void {
        $availability = VisitAvailability::query()
            ->where('contest_id', $application->contest_id)
            ->where('housing_unit_id', $housingUnit->id)
            ->where('staff_user_id', $visitManager->id)
            ->sole();

        $slots = VisitSlot::query()
            ->where('visit_availability_id', $availability->id)
            ->orderBy('starts_at')
            ->get();

        if ($slots->count() !== self::EXPECTED_SLOT_COUNT) {
            throw new LogicException(
                'O cenário final demo não possui quatro slots.',
            );
        }

        $visit = HousingVisit::query()
            ->where('application_id', $application->id)
            ->where('housing_unit_id', $housingUnit->id)
            ->where('candidate_user_id', $application->user_id)
            ->sole();

        if (
            $visit->status !== VisitStatus::Completed
            || (int) $visit->staff_user_id
                !== (int) $visitManager->id
            || $visit->statusHistories()->count() !== 4
        ) {
            throw new LogicException(
                'A visita final demo está incompleta.',
            );
        }

        $reports = ApplicationReport::query()
            ->where('application_id', $application->id)
            ->orderBy('format')
            ->get();

        $invalidReport = $reports->first(
            static function (
                ApplicationReport $report,
            ) use ($exporter): bool {
                $filePath = $report->getAttribute('file_path');

                return (int) $report->getAttribute('generated_by')
                        !== (int) $exporter->id
                    || (string) $report->getRawOriginal('status')
                        !== ApplicationReportStatus::Generated->value
                    || ! is_string($filePath)
                    || ! Storage::disk('local')->exists($filePath);
            },
        );

        if (
            $reports->count() !== 2
            || $invalidReport instanceof ApplicationReport
        ) {
            throw new LogicException(
                'Os relatórios finais demo estão incompletos.',
            );
        }

        $dossier = DocumentDossier::query()
            ->where('application_id', $application->id)
            ->with('items')
            ->sole();

        $dossierFilePath = $dossier->getAttribute('file_path');

        if (
            (string) $dossier->getRawOriginal('status')
                !== DocumentDossierStatus::Standardized->value
            || (int) $dossier->getAttribute('created_by')
                !== (int) $exporter->id
            || $dossier->items->count()
                !== self::EXPECTED_DOCUMENT_COUNT
            || ! is_string($dossierFilePath)
            || ! Storage::disk('local')->exists(
                $dossierFilePath,
            )
        ) {
            throw new LogicException(
                'O dossier final demo está incompleto.',
            );
        }

        $notifications = OfficialNotification::query()
            ->where(
                'notifiable_type',
                $visit->getMorphClass(),
            )
            ->where('notifiable_id', $visit->id)
            ->count();

        if ($notifications !== 4) {
            throw new LogicException(
                'O ciclo de visita demo não possui quatro notificações.',
            );
        }

        $documentVersionCount = DocumentVersion::query()
            ->whereIn(
                'document_submission_id',
                DocumentSubmission::query()
                    ->where('application_id', $application->id)
                    ->select('id'),
            )
            ->count();

        if (
            $application->status !== ApplicationStatus::Submitted
            || $application->snapshots()->count() !== 8
            || $application->applicationDocuments()->count()
                !== self::EXPECTED_DOCUMENT_COUNT
            || $documentVersionCount !== 16
        ) {
            throw new LogicException(
                'Visitas ou exportações alteraram os artefactos formais '
                .'da submissão.',
            );
        }
    }
}
