<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportFieldData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationResultExportSensitivity;

final class ApplicationResultExportFieldCatalog
{
    /** @return list<ApplicationResultExportFieldData> */
    public function all(): array
    {
        $applicationModes = ApplicationResultExportMode::cases();
        $deltaModes = [
            ApplicationResultExportMode::DeltaBetweenBatches,
            ApplicationResultExportMode::DeltaSinceDatetime,
        ];

        return [
            ...$this->applicationFields($applicationModes),
            ...$this->documentFields($applicationModes),
            ...$this->findingFields($applicationModes),
            ...$this->changeFields($deltaModes),
        ];
    }

    /**
     * @return list<ApplicationResultExportFieldData>
     */
    public function forDataset(
        ApplicationResultExportMode $mode,
        ApplicationResultExportDataset $dataset,
        bool $includeSensitive = false,
    ): array {
        return array_values(array_filter(
            $this->all(),
            static fn (ApplicationResultExportFieldData $field): bool => $field
                ->availableFor($mode, $dataset, $includeSensitive),
        ));
    }

    public function find(string $code): ?ApplicationResultExportFieldData
    {
        foreach ($this->all() as $field) {
            if ($field->code === $code) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param  list<ApplicationResultExportMode>  $modes
     * @return list<ApplicationResultExportFieldData>
     */
    private function applicationFields(array $modes): array
    {
        $dataset = [ApplicationResultExportDataset::Applications];
        $operational = ApplicationResultExportSensitivity::Operational;
        $reference = ApplicationResultExportSensitivity::ProcessReference;

        return [
            $this->field('municipality_code', 'Código do Município', 'string', 'municipality.code', $operational, false, $modes, $dataset),
            $this->field('contest_code', 'Código do concurso', 'string', 'contest.code', $operational, false, $modes, $dataset),
            $this->field('contest_public_id', 'Identificador público do concurso', 'string', 'contest.public_id', $reference, true, $modes, $dataset),
            $this->field('phase_code', 'Fase processual', 'string', 'source.phase', $operational, true, $modes, $dataset),
            $this->field('batch_public_id', 'Identificador público do lote', 'string', 'application_review_batches.public_id', $reference, true, $modes, $dataset),
            $this->field('batch_cycle', 'Ciclo do lote', 'string', 'application_review_batches.cycle', $operational, true, $modes, $dataset),
            $this->field('batch_sequence', 'Sequência do lote', 'integer', 'application_review_batches.sequence_number', $operational, true, $modes, $dataset),
            $this->field('snapshot_at', 'Momento do snapshot', 'datetime', 'source.snapshot_at', $operational, false, $modes, $dataset),
            $this->field('published_at', 'Momento da publicação', 'datetime', 'application_review_publications.published_at', $operational, true, $modes, $dataset),
            $this->field('application_number', 'Número da candidatura', 'string', 'applications.application_number', $reference, true, $modes, $dataset),
            $this->field('process_number', 'Número do processo', 'string', 'administrative_processes.process_number', $reference, true, $modes, $dataset),
            $this->field('candidate_name', 'Nome do candidato', 'string', 'users.name', ApplicationResultExportSensitivity::Personal, true, $modes, $dataset),
            $this->field('submission_status_code', 'Código do estado da candidatura', 'string', 'applications.status', $operational, true, $modes, $dataset),
            $this->field('submission_status_label', 'Estado da candidatura', 'string', 'applications.status.label', $operational, true, $modes, $dataset),
            $this->field('review_status_code', 'Código do estado da revisão', 'string', 'application_reviews.status', $operational, true, $modes, $dataset),
            $this->field('review_status_label', 'Estado da revisão', 'string', 'application_reviews.status.label', $operational, true, $modes, $dataset),
            $this->field('review_result_code', 'Código do resultado documental', 'string', 'application_review_batch_items.outcome', $operational, true, $modes, $dataset),
            $this->field('review_result_label', 'Resultado documental', 'string', 'application_review_batch_items.outcome.label', $operational, true, $modes, $dataset),
            $this->field('documents_required', 'Documentos obrigatórios', 'integer', 'review.readiness.total_required', $operational, true, $modes, $dataset),
            $this->field('documents_valid', 'Documentos válidos', 'integer', 'review.readiness.validated', $operational, true, $modes, $dataset),
            $this->field('documents_missing', 'Documentos em falta', 'integer', 'review.readiness.missing', $operational, true, $modes, $dataset),
            $this->field('documents_invalid', 'Documentos inválidos', 'integer', 'review.readiness.rejected_expired', $operational, true, $modes, $dataset),
            $this->field('correction_required', 'Aperfeiçoamento necessário', 'boolean', 'correction_requests', $operational, true, $modes, $dataset),
            $this->field('correction_deadline', 'Prazo de aperfeiçoamento', 'datetime', 'correction_requests.response_deadline_at', $operational, true, $modes, $dataset),
            $this->field('correction_submitted_at', 'Aperfeiçoamento submetido em', 'datetime', 'correction_requests.submitted_at', $operational, true, $modes, $dataset),
            $this->field('revalidation_result_code', 'Resultado da revalidação', 'string', 'correction_requests.revalidation_result', $operational, true, $modes, $dataset),
            $this->field('eligibility_status_code', 'Código da elegibilidade', 'string', 'eligibility_checks.result', $operational, true, $modes, $dataset),
            $this->field('eligibility_status_label', 'Elegibilidade', 'string', 'eligibility_checks.result.label', $operational, true, $modes, $dataset),
            $this->field('score_status_code', 'Código do estado da pontuação', 'string', 'application_scores.status', $operational, true, $modes, $dataset),
            $this->field('score_status_label', 'Estado da pontuação', 'string', 'application_scores.status.label', $operational, true, $modes, $dataset),
            $this->field('final_administrative_status_code', 'Código do estado administrativo', 'string', 'administrative_decisions.decision_result', $operational, true, $modes, $dataset),
            $this->field('final_administrative_status_label', 'Estado administrativo', 'string', 'administrative_decisions.decision_result.label', $operational, true, $modes, $dataset),
            $this->field('last_changed_at', 'Última alteração', 'datetime', 'source.last_changed_at', $operational, true, $modes, $dataset),
            $this->field('source_fingerprint', 'Fingerprint da fonte', 'string', 'export.source_fingerprint', $operational, false, $modes, $dataset),
        ];
    }

    /**
     * @param  list<ApplicationResultExportMode>  $modes
     * @return list<ApplicationResultExportFieldData>
     */
    private function documentFields(array $modes): array
    {
        $dataset = [ApplicationResultExportDataset::Documents];
        $operational = ApplicationResultExportSensitivity::Operational;
        $reference = ApplicationResultExportSensitivity::ProcessReference;

        return [
            $this->field('application_number', 'Número da candidatura', 'string', 'applications.application_number', $reference, true, $modes, $dataset),
            $this->field('process_number', 'Número do processo', 'string', 'administrative_processes.process_number', $reference, true, $modes, $dataset),
            $this->field('required_document_code', 'Código do requisito documental', 'string', 'required_documents.id_or_code', $operational, true, $modes, $dataset),
            $this->field('document_type_code', 'Código do tipo documental', 'string', 'document_types.code', $operational, true, $modes, $dataset),
            $this->field('target_type', 'Tipo de alvo', 'string', 'document_submissions.target', $operational, true, $modes, $dataset),
            $this->field('target_reference', 'Referência do alvo', 'string', 'document_submissions.target_id', $reference, true, $modes, $dataset),
            $this->field('requirement_instance', 'Instância do requisito', 'integer', 'document_submissions.requirement_instance', $operational, false, $modes, $dataset),
            $this->field('required_submissions', 'Submissões exigidas', 'integer', 'required_documents.required_submissions', $operational, true, $modes, $dataset),
            $this->field('reference_period', 'Período de referência', 'date', 'document_submissions.reference_period', $operational, true, $modes, $dataset),
            $this->field('document_status_code', 'Código do estado documental', 'string', 'document_submissions.status', $operational, true, $modes, $dataset),
            $this->field('version_number', 'Versão', 'integer', 'document_versions.version_number', $operational, true, $modes, $dataset),
            $this->field('submitted_at', 'Submetido em', 'datetime', 'document_submissions.submitted_at', $operational, true, $modes, $dataset),
            $this->field('validated_at', 'Validado em', 'datetime', 'document_submissions.validated_at', $operational, true, $modes, $dataset),
            $this->field('source_sha256', 'SHA-256 de origem', 'string', 'document_versions.checksum', $operational, true, $modes, $dataset),
            $this->field('carried_forward', 'Transportado do ciclo anterior', 'boolean', 'snapshot.carried_forward', $operational, false, $modes, $dataset),
            $this->field('source_batch_public_id', 'Lote de origem', 'string', 'application_review_batches.public_id', $reference, true, $modes, $dataset),
        ];
    }

    /**
     * @param  list<ApplicationResultExportMode>  $modes
     * @return list<ApplicationResultExportFieldData>
     */
    private function findingFields(array $modes): array
    {
        $dataset = [ApplicationResultExportDataset::Findings];
        $operational = ApplicationResultExportSensitivity::Operational;
        $reference = ApplicationResultExportSensitivity::ProcessReference;

        return [
            $this->field('application_number', 'Número da candidatura', 'string', 'applications.application_number', $reference, true, $modes, $dataset),
            $this->field('finding_code', 'Código do achado', 'string', 'snapshot.findings.key', $operational, false, $modes, $dataset),
            $this->field('requirement_code', 'Código do requisito', 'string', 'snapshot.findings.requirement', $operational, true, $modes, $dataset),
            $this->field('finding_status_code', 'Código do estado do achado', 'string', 'snapshot.findings.status', $operational, true, $modes, $dataset),
            $this->field('finding_status_label', 'Estado do achado', 'string', 'snapshot.findings.status.label', $operational, true, $modes, $dataset),
            $this->field('decision_code', 'Código da decisão', 'string', 'correction_responses.review_result', $operational, true, $modes, $dataset),
            $this->field('carried_forward', 'Transportado do ciclo anterior', 'boolean', 'snapshot.carried_forward', $operational, false, $modes, $dataset),
            $this->field('source_batch_public_id', 'Lote de origem', 'string', 'application_review_batches.public_id', $reference, true, $modes, $dataset),
            $this->field('decided_at', 'Decidido em', 'datetime', 'correction_responses.reviewed_at', $operational, true, $modes, $dataset),
            $this->field('resolved_at', 'Resolvido em', 'datetime', 'correction_requests.resolved_at', $operational, true, $modes, $dataset),
        ];
    }

    /**
     * @param  list<ApplicationResultExportMode>  $modes
     * @return list<ApplicationResultExportFieldData>
     */
    private function changeFields(array $modes): array
    {
        $dataset = [ApplicationResultExportDataset::Changes];
        $operational = ApplicationResultExportSensitivity::Operational;
        $reference = ApplicationResultExportSensitivity::ProcessReference;

        return [
            $this->field('entity_type', 'Tipo de entidade', 'string', 'comparator.entity_type', $operational, false, $modes, $dataset),
            $this->field('entity_reference', 'Referência da entidade', 'string', 'comparator.entity_reference', $reference, false, $modes, $dataset),
            $this->field('application_number', 'Número da candidatura', 'string', 'applications.application_number', $reference, true, $modes, $dataset),
            $this->field('change_type', 'Tipo de alteração', 'string', 'comparator.change_type', $operational, false, $modes, $dataset),
            $this->field('field_code', 'Campo alterado', 'string', 'comparator.field_code', $operational, true, $modes, $dataset),
            // A sensibilidade destes valores e determinada pelo campo comparado.
            $this->field('before_value', 'Valor anterior', 'mixed', 'comparator.before', $operational, true, $modes, $dataset),
            $this->field('after_value', 'Valor posterior', 'mixed', 'comparator.after', $operational, true, $modes, $dataset),
            $this->field('before_source', 'Fonte anterior', 'string', 'comparator.before_source', $reference, true, $modes, $dataset),
            $this->field('after_source', 'Fonte posterior', 'string', 'comparator.after_source', $reference, true, $modes, $dataset),
            $this->field('changed_at', 'Alterado em', 'datetime', 'comparator.changed_at', $operational, true, $modes, $dataset),
            $this->field('sensitive_value_redacted', 'Valor sensível ocultado', 'boolean', 'comparator.redaction', $operational, false, $modes, $dataset),
        ];
    }

    /**
     * @param  list<ApplicationResultExportMode>  $modes
     * @param  list<ApplicationResultExportDataset>  $datasets
     */
    private function field(
        string $code,
        string $label,
        string $type,
        string $source,
        ApplicationResultExportSensitivity $sensitivity,
        bool $nullable,
        array $modes,
        array $datasets,
    ): ApplicationResultExportFieldData {
        return new ApplicationResultExportFieldData(
            code: $code,
            label: $label,
            type: $type,
            source: $source,
            sensitivity: $sensitivity,
            nullable: $nullable,
            availableInModes: $modes,
            availableInDatasets: $datasets,
        );
    }
}
