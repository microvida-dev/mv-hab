<?php

namespace App\Jobs;

use App\Models\DocumentTemplate;
use App\Models\User;
use App\Services\Documents\OfficialDocumentGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateOfficialDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        public readonly int $templateId,
        public readonly array $variables,
        public readonly int $actorId,
        public readonly ?int $recipientId = null,
        public readonly bool $issueImmediately = false,
    ) {}

    public function handle(OfficialDocumentGenerationService $service): void
    {
        $service->generate(
            DocumentTemplate::query()->findOrFail($this->templateId),
            $this->variables,
            User::query()->findOrFail($this->actorId),
            $this->recipientId ? User::query()->find($this->recipientId) : null,
            issueImmediately: $this->issueImmediately,
        );
    }
}
