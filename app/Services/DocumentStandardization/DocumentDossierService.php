<?php

namespace App\Services\DocumentStandardization;

use App\Enums\DocumentDossierStatus;
use App\Models\Application;
use App\Models\DocumentDossier;
use App\Models\DocumentDossierItem;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class DocumentDossierService
{
    public function __construct(
        private readonly DocumentDossierBuilder $builder,
        private readonly DocumentDossierExportService $exporter,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(Application $application, User $actor, array $options = []): DocumentDossier
    {
        return DB::transaction(function () use ($application, $actor, $options): DocumentDossier {
            $payload = $this->builder->build($application, $options);
            $summary = $payload['summary'];
            $status = $summary['missing'] > 0
                ? DocumentDossierStatus::Incomplete
                : ($summary['rejected'] > 0 || $summary['expired'] > 0 ? DocumentDossierStatus::RequiresReview : DocumentDossierStatus::Standardized);

            $dossier = new DocumentDossier([
                'title' => 'Dossier documental '.$application->application_number,
                'summary' => 'Índice documental padronizado gerado automaticamente para validação municipal.',
            ]);
            $dossier->forceFill([
                'dossier_number' => $this->number(),
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'contest_id' => $application->contest_id,
                'status' => $status,
                'standardization_payload' => $payload,
                'missing_documents_count' => $summary['missing'],
                'rejected_documents_count' => $summary['rejected'],
                'expired_documents_count' => $summary['expired'],
                'validated_documents_count' => $summary['validated'],
                'standardized_at' => now(),
                'created_by' => $actor->id,
            ])->save();

            foreach ($payload['items'] as $item) {
                $record = new DocumentDossierItem([
                    'category' => $item['category'],
                    'label' => $item['label'],
                    'notes' => $item['notes'],
                ]);
                $record->forceFill([
                    'document_dossier_id' => $dossier->id,
                    'document_submission_id' => $item['document_submission_id'],
                    'required_document_id' => $item['required_document_id'],
                    'document_type_id' => $item['document_type_id'],
                    'status' => $item['status'],
                    'sort_order' => $item['sort_order'],
                    'is_required' => $item['is_required'],
                    'is_missing' => $item['is_missing'],
                    'is_rejected' => $item['is_rejected'],
                    'is_expired' => $item['is_expired'],
                    'is_validated' => $item['is_validated'],
                ])->save();
            }

            $dossier->forceFill(['file_path' => $this->exporter->export($dossier->refresh())])->save();

            $this->auditLogger->record(
                AuditEvents::EXPORT,
                $dossier,
                'documents',
                'document_dossier_generate',
                'Dossier documental padronizado gerado.',
                metadata: ['application_id' => $application->id],
            );

            return $dossier->refresh();
        });
    }

    private function number(): string
    {
        $next = DocumentDossier::withTrashed()->count() + 1;

        do {
            $number = 'DOS-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (DocumentDossier::withTrashed()->where('dossier_number', $number)->exists());

        return $number;
    }
}
