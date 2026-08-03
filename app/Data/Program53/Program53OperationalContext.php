<?php

namespace App\Data\Program53;

final readonly class Program53OperationalContext
{
    public function __construct(
        public string $operationId,
        public ?string $requestId = null,
        public ?string $correlationId = null,
        public ?int $municipalityId = null,
        public ?int $contestId = null,
        public ?int $batchId = null,
        public ?int $publicationId = null,
        public ?int $correctionRequestId = null,
        public ?int $exportId = null,
        public ?string $jobId = null,
        public int $attempt = 1,
        public ?string $stage = null,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'operation_id' => $this->operationId,
            'request_id' => $this->requestId,
            'correlation_id' => $this->correlationId,
            'municipality_id' => $this->municipalityId,
            'contest_id' => $this->contestId,
            'batch_id' => $this->batchId,
            'publication_id' => $this->publicationId,
            'correction_request_id' => $this->correctionRequestId,
            'export_id' => $this->exportId,
            'job_id' => $this->jobId,
            'attempt' => max(1, $this->attempt),
            'stage' => $this->stage,
        ];
    }

    public function withStage(string $stage): self
    {
        return new self(
            operationId: $this->operationId,
            requestId: $this->requestId,
            correlationId: $this->correlationId,
            municipalityId: $this->municipalityId,
            contestId: $this->contestId,
            batchId: $this->batchId,
            publicationId: $this->publicationId,
            correctionRequestId: $this->correctionRequestId,
            exportId: $this->exportId,
            jobId: $this->jobId,
            attempt: $this->attempt,
            stage: $stage,
        );
    }
}
