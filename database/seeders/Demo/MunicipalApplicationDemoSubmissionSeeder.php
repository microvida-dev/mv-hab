<?php

namespace Database\Seeders\Demo;

use App\Enums\ApplicationDeclarationType;
use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Household;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Applications\ApplicationSubmissionService;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Documents\DocumentUploadService;
use App\Services\Documents\RequiredDocumentResolver;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;

final class MunicipalApplicationDemoSubmissionSeeder extends Seeder
{
    private const EXPECTED_DOCUMENT_COUNT = 15;

    /**
     * @var list<string>
     */
    private const REQUIRED_DOCUMENT_CODES = [
        'alcanena_demo_identificacao_residencia',
        'alcanena_demo_nif',
        'alcanena_demo_nota_liquidacao_irs',
        'alcanena_demo_situacao_regular_at',
        'alcanena_demo_situacao_regular_iss',
        'recibos_vencimento',
    ];

    /**
     * @var list<string>
     */
    private const PAYSLIP_REFERENCE_PERIODS = [
        '2026-07',
        '2026-06',
        '2026-05',
    ];

    public function run(): void
    {
        $context = app(MunicipalApplicationDemoContext::class);
        $context->assertSeederAllowed();

        $candidate = $this->candidate();
        $application = $this->application($candidate);

        if ($application->status === ApplicationStatus::Submitted) {
            $this->assertFinalState($application);

            return;
        }

        if ($application->status !== ApplicationStatus::Draft) {
            throw new LogicException(
                'A candidatura municipal demo só pode ser preparada a partir '
                .'dos estados draft ou submitted.',
            );
        }

        $previousDocumentAiState = config('document-ai.enabled');

        try {
            config()->set('document-ai.enabled', false);

            $this->ensureDocuments(
                $application,
                $candidate,
            );
        } finally {
            config()->set(
                'document-ai.enabled',
                $previousDocumentAiState,
            );
        }

        $this->assertChecklistComplete($application);

        $application->refresh();

        $submitted = app(ApplicationSubmissionService::class)
            ->submit(
                $application,
                $candidate,
            );

        $this->assertFinalState($submitted);
    }

    private function candidate(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
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
                'adhesionRegistration',
                'household.members',
                'household.incomeRecords.householdMember',
                'contest',
                'program',
            ])
            ->sole();
    }

    private function ensureDocuments(
        Application $application,
        User $candidate,
    ): void {
        $registration = AdhesionRegistration::query()
            ->whereKey(
                $application->adhesion_registration_id,
            )
            ->firstOrFail();

        $household = $application->household;

        if (! $household instanceof Household) {
            throw new LogicException(
                'A candidatura demo não possui agregado familiar.',
            );
        }

        $members = $household->members()
            ->orderByDesc('is_applicant')
            ->orderBy('id')
            ->get();

        if ($members->count() !== 3) {
            throw new LogicException(
                'A checklist demo exige exatamente três elementos no agregado.',
            );
        }

        $incomeRecords = $household->incomeRecords()
            ->with('householdMember')
            ->orderBy('id')
            ->get();

        if ($incomeRecords->count() !== 2) {
            throw new LogicException(
                'A checklist demo exige exatamente dois rendimentos de trabalho.',
            );
        }

        $requirements = $this->requirements($application);
        $sequence = 0;

        foreach ($members as $member) {
            $sequence++;

            $this->ensureSubmission(
                registration: $registration,
                application: $application,
                candidate: $candidate,
                requiredDocument: $requirements->get(
                    'alcanena_demo_identificacao_residencia',
                ),
                sequence: $sequence,
                target: [
                    'household_member_id' => $member->id,
                ],
            );
        }

        foreach ($members as $member) {
            $sequence++;

            $this->ensureSubmission(
                registration: $registration,
                application: $application,
                candidate: $candidate,
                requiredDocument: $requirements->get(
                    'alcanena_demo_nif',
                ),
                sequence: $sequence,
                target: [
                    'household_member_id' => $member->id,
                ],
            );
        }

        $sequence++;

        $this->ensureSubmission(
            registration: $registration,
            application: $application,
            candidate: $candidate,
            requiredDocument: $requirements->get(
                'alcanena_demo_nota_liquidacao_irs',
            ),
            sequence: $sequence,
            target: [
                'household_id' => $household->id,
            ],
        );

        $sequence++;

        $this->ensureSubmission(
            registration: $registration,
            application: $application,
            candidate: $candidate,
            requiredDocument: $requirements->get(
                'alcanena_demo_situacao_regular_at',
            ),
            sequence: $sequence,
        );

        $sequence++;

        $this->ensureSubmission(
            registration: $registration,
            application: $application,
            candidate: $candidate,
            requiredDocument: $requirements->get(
                'alcanena_demo_situacao_regular_iss',
            ),
            sequence: $sequence,
        );

        foreach ($incomeRecords as $incomeRecord) {
            foreach (
                self::PAYSLIP_REFERENCE_PERIODS as $index => $referencePeriod
            ) {
                $sequence++;

                $this->ensureSubmission(
                    registration: $registration,
                    application: $application,
                    candidate: $candidate,
                    requiredDocument: $requirements->get(
                        'recibos_vencimento',
                    ),
                    sequence: $sequence,
                    target: [
                        'income_record_id' => $incomeRecord->id,
                    ],
                    requirementInstance: $index + 1,
                    referencePeriod: $referencePeriod,
                );
            }
        }

        if ($sequence !== self::EXPECTED_DOCUMENT_COUNT) {
            throw new LogicException(
                'O plano documental demo não produziu exatamente 15 posições.',
            );
        }
    }

    /**
     * @return Collection<string, RequiredDocument>
     */
    private function requirements(
        Application $application,
    ): Collection {
        $resolved = app(RequiredDocumentResolver::class)
            ->resolve(
                programId: $application->program_id,
                contestId: $application->contest_id,
            )
            ->filter(
                static fn (
                    RequiredDocument $requirement,
                ): bool => in_array(
                    $requirement->documentType?->code,
                    self::REQUIRED_DOCUMENT_CODES,
                    true,
                ),
            )
            ->keyBy(
                static fn (
                    RequiredDocument $requirement,
                ): string => (string) $requirement
                    ->documentType
                    ?->code,
            );

        if (
            $resolved->count()
            !== count(self::REQUIRED_DOCUMENT_CODES)
        ) {
            throw new LogicException(
                'A checklist demo não contém as seis regras documentais esperadas.',
            );
        }

        foreach (self::REQUIRED_DOCUMENT_CODES as $code) {
            $requirement = $resolved->get($code);

            if (! $requirement instanceof RequiredDocument) {
                throw new LogicException(
                    "A regra documental demo {$code} não foi resolvida.",
                );
            }

            if (
                $requirement->program_id !== $application->program_id
                || $requirement->contest_id !== $application->contest_id
                || ! $requirement->is_required
                || ! $requirement->is_active
            ) {
                throw new LogicException(
                    "A regra documental demo {$code} possui âmbito incompatível.",
                );
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, int>  $target
     */
    private function ensureSubmission(
        AdhesionRegistration $registration,
        Application $application,
        User $candidate,
        mixed $requiredDocument,
        int $sequence,
        array $target = [],
        int $requirementInstance = 1,
        ?string $referencePeriod = null,
    ): DocumentSubmission {
        if (! $requiredDocument instanceof RequiredDocument) {
            throw new LogicException(
                'A posição documental demo não possui uma regra válida.',
            );
        }

        $existing = $this->existingSubmissions(
            registration: $registration,
            application: $application,
            requiredDocument: $requiredDocument,
            target: $target,
            requirementInstance: $requirementInstance,
        );

        if ($existing->count() > 1) {
            throw new LogicException(
                'Existem múltiplas submissões para a mesma posição documental demo.',
            );
        }

        $submission = $existing->first();

        if ($submission instanceof DocumentSubmission) {
            $this->assertExistingSubmission(
                submission: $submission,
                registration: $registration,
                application: $application,
                candidate: $candidate,
                requiredDocument: $requiredDocument,
                target: $target,
                sequence: $sequence,
                requirementInstance: $requirementInstance,
                referencePeriod: $referencePeriod,
            );

            return $submission;
        }

        if ($application->status !== ApplicationStatus::Draft) {
            throw new LogicException(
                'Não é possível recriar um documento em falta depois da '
                .'submissão formal da candidatura demo.',
            );
        }

        $file = $this->temporaryPdf($sequence);

        try {
            $data = [
                'required_document_id' => $requiredDocument->id,
                'document_type_id' => $requiredDocument->document_type_id,
                'application_public_id' => $application->public_id,
                'requirement_instance' => $requirementInstance,
                'title' => sprintf(
                    'Documento demo %03d',
                    $sequence,
                ),
                'notes' => 'Ficheiro inteiramente fictício, criado apenas '
                    .'para demonstração funcional e sem efeitos administrativos.',
                ...$target,
            ];

            if ($referencePeriod !== null) {
                $data['reference_period'] = $referencePeriod;
            }

            $submission = app(DocumentUploadService::class)
                ->store(
                    $registration,
                    $file,
                    $data,
                    $candidate,
                );
        } finally {
            $temporaryPath = $file->getPathname();

            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $this->assertExistingSubmission(
            submission: $submission,
            registration: $registration,
            application: $application,
            candidate: $candidate,
            requiredDocument: $requiredDocument,
            target: $target,
            sequence: $sequence,
            requirementInstance: $requirementInstance,
            referencePeriod: $referencePeriod,
        );

        return $submission;
    }

    /**
     * @param  array<string, int>  $target
     * @return Collection<int, DocumentSubmission>
     */
    private function existingSubmissions(
        AdhesionRegistration $registration,
        Application $application,
        RequiredDocument $requiredDocument,
        array $target,
        int $requirementInstance,
    ): Collection {
        $query = DocumentSubmission::withTrashed()
            ->where(
                'adhesion_registration_id',
                $registration->id,
            )
            ->where('application_id', $application->id)
            ->where(
                'required_document_id',
                $requiredDocument->id,
            )
            ->where(
                'requirement_instance',
                $requirementInstance,
            );

        foreach ($target as $column => $value) {
            $query->where($column, $value);
        }

        return $query
            ->with([
                'currentVersion',
                'versions',
            ])
            ->get();
    }

    /**
     * @param  array<string, int>  $target
     */
    private function assertExistingSubmission(
        DocumentSubmission $submission,
        AdhesionRegistration $registration,
        Application $application,
        User $candidate,
        RequiredDocument $requiredDocument,
        array $target,
        int $sequence,
        int $requirementInstance,
        ?string $referencePeriod,
    ): void {
        if ($submission->trashed()) {
            throw new LogicException(
                'A submissão documental demo existente encontra-se eliminada.',
            );
        }

        if (
            (int) $submission->adhesion_registration_id
                !== (int) $registration->id
            || (int) $submission->application_id
                !== (int) $application->id
            || (int) $submission->user_id
                !== (int) $candidate->id
            || (int) $submission->submitted_by
                !== (int) $candidate->id
            || (int) $submission->required_document_id
                !== (int) $requiredDocument->id
            || (int) $submission->document_type_id
                !== (int) $requiredDocument->document_type_id
            || (int) $submission->requirement_instance
                !== $requirementInstance
            || (string) $submission->getRawOriginal('status')
                !== DocumentStatus::Submitted->value
        ) {
            throw new LogicException(
                'A submissão documental demo existente possui dados incompatíveis.',
            );
        }

        foreach ($target as $column => $value) {
            if ((int) $submission->getAttribute($column) !== $value) {
                throw new LogicException(
                    'A submissão documental demo está associada ao alvo incorreto.',
                );
            }
        }

        $actualReferencePeriod = $submission
            ->reference_period
            ?->format('Y-m');

        if ($actualReferencePeriod !== $referencePeriod) {
            throw new LogicException(
                'A submissão documental demo possui um período de referência '
                .'incompatível.',
            );
        }

        $submission->loadMissing([
            'currentVersion',
            'versions',
        ]);

        $version = $submission->getRelationValue(
            'currentVersion',
        );

        if (! $version instanceof DocumentVersion) {
            throw new LogicException(
                'A submissão documental demo não possui versão atual.',
            );
        }

        if (
            $submission->versions->count() !== 1
            || (int) $version->getAttribute('version_number') !== 1
            || (string) $version->getRawOriginal(
                'status_at_upload',
            ) !== DocumentStatus::Submitted->value
            || (int) $version->getAttribute('uploaded_by')
                !== (int) $candidate->id
            || (string) $version->getAttribute('storage_disk')
                !== 'local'
            || (string) $version->getAttribute('mime_type')
                !== 'application/pdf'
            || (string) $version->getAttribute(
                'original_filename',
            ) !== sprintf(
                'demo-document-%03d.pdf',
                $sequence,
            )
            || (int) $submission->getAttribute(
                'current_version_id',
            ) !== (int) $version->id
        ) {
            throw new LogicException(
                'A versão documental demo existente possui dados incompatíveis.',
            );
        }

        if (
            ! Storage::disk($version->storage_disk)
                ->exists($version->storage_path)
        ) {
            throw new LogicException(
                'O ficheiro privado da versão documental demo não existe.',
            );
        }

        $contents = Storage::disk($version->storage_disk)
            ->get($version->storage_path);

        if (! is_string($contents)) {
            throw new LogicException(
                'Não foi possível ler o ficheiro privado da versão documental demo.',
            );
        }

        if (
            ! str_starts_with($contents, '%PDF-')
            || hash('sha256', $contents) !== $version->checksum
            || strlen($contents) !== $version->file_size
            || $submission->checksum !== $version->checksum
            || $submission->storage_path !== $version->storage_path
        ) {
            throw new LogicException(
                'A integridade do ficheiro privado demo não é válida.',
            );
        }
    }

    private function assertChecklistComplete(
        Application $application,
    ): void {
        $application->refresh();

        $checklist = app(DocumentChecklistService::class)
            ->forApplication($application);

        $summary = $checklist['summary'] ?? null;

        if (
            ! is_array($summary)
            || ($summary['total_required'] ?? null)
                !== self::EXPECTED_DOCUMENT_COUNT
            || ($summary['missing'] ?? null) !== 0
            || ($summary['submitted'] ?? null)
                !== self::EXPECTED_DOCUMENT_COUNT
            || ($summary['rejected'] ?? null) !== 0
            || ($summary['percentage'] ?? null) !== 100
        ) {
            throw new LogicException(
                'A checklist documental demo não está completa para submissão.',
            );
        }
    }

    private function assertFinalState(
        Application $application,
    ): void {
        $application = $application->fresh([
            'declarations',
            'snapshots',
            'applicationDocuments.documentSubmission.currentVersion',
            'housingPreferences',
        ]);

        if (! $application instanceof Application) {
            throw new LogicException(
                'A candidatura demo submetida deixou de existir.',
            );
        }

        if (
            $application->status !== ApplicationStatus::Submitted
            || blank($application->application_number)
            || $application->submitted_at === null
            || $application->locked_at === null
            || $application->regulatory_snapshot_id === null
            || ! $application->declaration_accepted
            || ! $application->contest_rules_accepted
            || ! $application->data_processing_accepted
            || ! $application->truthfulness_accepted
            || ! $application->data_current_confirmed
        ) {
            throw new LogicException(
                'A candidatura municipal demo não está formalmente submetida.',
            );
        }

        if (
            $application->declarations->count()
                !== count(ApplicationDeclarationType::cases())
            || $application->snapshots->count()
                !== count(ApplicationSnapshotType::cases())
            || $application->applicationDocuments->count()
                !== self::EXPECTED_DOCUMENT_COUNT
            || $application->housingPreferences->count() !== 3
        ) {
            throw new LogicException(
                'Os artefactos finais da candidatura demo estão incompletos.',
            );
        }

        foreach ($application->housingPreferences as $preference) {
            if (
                $preference->submitted_at === null
                || $preference->locked_at === null
                || $preference->invalidated_at !== null
            ) {
                throw new LogicException(
                    'As preferências da candidatura demo não estão bloqueadas.',
                );
            }
        }

        $this->assertChecklistComplete($application);
    }

    private function temporaryPdf(
        int $sequence,
    ): UploadedFile {
        $path = tempnam(
            sys_get_temp_dir(),
            'mvhab-demo-document-',
        );

        if ($path === false) {
            throw new RuntimeException(
                'Não foi possível criar o ficheiro temporário demo.',
            );
        }

        $contents = $this->pdfContents($sequence);

        if (file_put_contents($path, $contents) === false) {
            unlink($path);

            throw new RuntimeException(
                'Não foi possível escrever o ficheiro PDF demo.',
            );
        }

        return new UploadedFile(
            path: $path,
            originalName: sprintf(
                'demo-document-%03d.pdf',
                $sequence,
            ),
            mimeType: 'application/pdf',
            error: null,
            test: true,
        );
    }

    private function pdfContents(int $sequence): string
    {
        $stream = implode("\n", [
            'BT',
            '/F1 12 Tf',
            '72 720 Td',
            sprintf(
                '(MV-HAB Demo Document %03d) Tj',
                $sequence,
            ),
            '0 -20 Td',
            '(Fictional data - no administrative effect) Tj',
            'ET',
            '',
        ]);

        $objects = [
            "1 0 obj\n"
                ."<< /Type /Catalog /Pages 2 0 R >>\n"
                ."endobj\n",
            "2 0 obj\n"
                ."<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n"
                ."endobj\n",
            "3 0 obj\n"
                .'<< /Type /Page /Parent 2 0 R '
                .'/MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 5 0 R >> >> '
                ."/Contents 4 0 R >>\n"
                ."endobj\n",
            "4 0 obj\n"
                .'<< /Length '.strlen($stream)." >>\n"
                ."stream\n"
                .$stream
                ."endstream\n"
                ."endobj\n",
            "5 0 obj\n"
                .'<< /Type /Font /Subtype /Type1 '
                ."/BaseFont /Helvetica >>\n"
                ."endobj\n",
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 6\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf(
                "%010d 00000 n \n",
                $offset,
            );
        }

        $pdf .= "trailer\n";
        $pdf .= "<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xrefOffset."\n";
        $pdf .= "%%EOF\n";

        return $pdf;
    }
}
