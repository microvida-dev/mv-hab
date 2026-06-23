<?php

namespace App\Services\Documents;

use App\Enums\DocumentGenerationStatus;
use App\Enums\TemplateStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\GeneratedOfficialDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\CommunicationNumberService;
use App\Services\Notifications\TemplateRenderingService;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfficialDocumentGenerationService
{
    public function __construct(
        private readonly CommunicationNumberService $numbers,
        private readonly TemplateRenderingService $renderer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     */
    public function generate(DocumentTemplate $template, array $variables, User $actor, ?User $recipient = null, ?Model $related = null, bool $issueImmediately = false): GeneratedOfficialDocument
    {
        $template->loadMissing('activeVersion');
        $version = $template->activeVersion;
        if ($template->status !== TemplateStatus::Active || ! ($version instanceof DocumentTemplateVersion)) {
            throw ValidationException::withMessages(['document_template_id' => 'O modelo documental não tem uma versão ativa.']);
        }

        $rendered = $this->renderer->render([
            'title' => $version->title,
            'body' => $version->body,
            'html_body' => $version->html_body,
            'header' => $version->header,
            'footer' => $version->footer,
        ], $variables);
        $number = $this->numbers->document();
        $html = view('documents.official.generic-document', [
            'documentNumber' => $number,
            'title' => $rendered['title'],
            'header' => $rendered['header'],
            'body' => nl2br(e($rendered['body'])),
            'footer' => $rendered['footer'],
        ])->render();
        $path = 'official-documents/'.now()->format('Y/m').'/'.$number.'.html';
        Storage::disk('local')->put($path, $html);

        $document = new GeneratedOfficialDocument([
            'document_template_id' => $template->id,
            'document_template_version_id' => $version->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'recipient_user_id' => $recipient?->id,
            'recipient_name' => $recipient?->name,
            'title' => $rendered['title'],
        ]);
        $document->forceFill([
            'document_number' => $number,
            'status' => $issueImmediately ? DocumentGenerationStatus::Issued : DocumentGenerationStatus::Generated,
            'html_content' => $html,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'text/html',
            'file_size' => strlen($html),
            'checksum' => hash('sha256', $html),
            'generated_by' => $actor->id,
            'generated_at' => now(),
            'issued_by' => $issueImmediately ? $actor->id : null,
            'issued_at' => $issueImmediately ? now() : null,
        ])->save();
        $this->audit->record(AuditEvents::CREATE, $document, 'notifications', 'official_document_generated', 'Documento oficial HTML gerado.');

        return $document;
    }

    public function issue(GeneratedOfficialDocument $document, User $actor): GeneratedOfficialDocument
    {
        $document->forceFill([
            'status' => DocumentGenerationStatus::Issued,
            'issued_by' => $actor->id,
            'issued_at' => now(),
        ])->save();

        return $document->refresh();
    }

    public function cancel(GeneratedOfficialDocument $document, User $actor, string $reason): GeneratedOfficialDocument
    {
        $document->forceFill([
            'status' => DocumentGenerationStatus::Cancelled,
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ])->save();

        return $document->refresh();
    }
}
