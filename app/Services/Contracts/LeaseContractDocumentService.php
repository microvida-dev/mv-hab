<?php

namespace App\Services\Contracts;

use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\LeaseContractDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseContractDocumentService
{
    public function __construct(
        private readonly ContractClauseService $clauseService,
        private readonly ContractPlaceholderService $placeholderService,
        private readonly LeaseContractPdfService $pdfService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function generate(Contract $contract, User $actor): LeaseContractDocument
    {
        $contract->loadMissing(['contractTemplate', 'clauses', 'deposit', 'rentCalculation']);
        if (! $contract->contractTemplate instanceof ContractTemplate) {
            throw ValidationException::withMessages([
                'contract_template' => 'O contrato não tem minuta associada para geração documental.',
            ]);
        }

        $version = (int) $contract->generatedDocuments()->max('version_number') + 1;
        $clausesHtml = $this->clauseService->renderClausesHtml($contract);
        $body = $this->placeholderService->render($contract->contractTemplate->template_body, $contract, $clausesHtml);
        $html = view('contracts.documents.lease-contract', [
            'contract' => $contract,
            'body' => $body,
            'pdfAvailable' => $this->pdfService->isAvailable(),
        ])->render();

        $path = sprintf('contracts/%d/contract-%d-v%d.html', $contract->id, $contract->id, $version);
        Storage::disk('local')->put($path, $html);
        $checksum = hash('sha256', $html);

        $document = $contract->generatedDocuments()->create([
            'status' => ContractDocumentStatus::Generated,
            'document_type' => ContractDocumentType::ContractHtml,
            'version_number' => $version,
            'title' => 'Contrato '.$contract->contract_number.' v'.$version,
            'html_content' => $html,
            'storage_disk' => 'local',
            'mime_type' => 'text/html',
        ]);
        $document->forceFill([
            'storage_path' => $path,
            'file_size' => strlen($html),
            'checksum' => $checksum,
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $document, 'contracts', 'lease_contract_document_generate', 'Documento contratual HTML gerado.', metadata: ['pdf_available' => false]);

        return $document->refresh();
    }

    public function download(LeaseContractDocument $document, User $actor): StreamedResponse
    {
        if ($document->storage_path === null) {
            throw ValidationException::withMessages([
                'document' => 'O documento contratual não tem ficheiro associado.',
            ]);
        }

        $this->auditLogger->record(AuditEvents::ACCESS, $document, 'contracts', 'lease_contract_document_download', 'Download autorizado de documento contratual.');

        return Storage::disk($document->storage_disk ?? 'local')->download(
            $document->storage_path,
            'contrato-'.$document->lease_contract_id.'-v'.$document->version_number.'.html',
            ['Content-Type' => $document->mime_type ?? 'text/html'],
        );
    }
}
