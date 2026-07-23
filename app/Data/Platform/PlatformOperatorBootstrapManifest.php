<?php

namespace App\Data\Platform;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class PlatformOperatorBootstrapManifest
{
    /**
     * @param  list<int>  $approvedUserIds
     * @param  list<string>  $approvalReferences
     */
    public function __construct(
        public string $environment,
        public array $approvedUserIds,
        public array $approvalReferences,
        public string $bootstrapOperatorReference,
        public string $approvedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $environment = self::requiredString($payload, 'environment', 80);
        $approvedUserIds = self::approvedUserIds($payload['approved_user_ids'] ?? null);
        $approvalReferences = self::approvalReferences($payload['approval_references'] ?? null);
        $bootstrapOperatorReference = self::requiredString(
            $payload,
            'bootstrap_operator_reference',
            160,
        );
        $approvedAt = self::requiredString($payload, 'approved_at', 10);
        $approvedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $approvedAt);

        if (! $approvedDate instanceof DateTimeImmutable || $approvedDate->format('Y-m-d') !== $approvedAt) {
            throw new InvalidArgumentException('O campo approved_at deve usar o formato YYYY-MM-DD.');
        }

        return new self(
            environment: $environment,
            approvedUserIds: $approvedUserIds,
            approvalReferences: $approvalReferences,
            bootstrapOperatorReference: $bootstrapOperatorReference,
            approvedAt: $approvedAt,
        );
    }

    public function primaryApprovalReference(): string
    {
        return $this->approvalReferences[0];
    }

    public function secondaryApprovalReference(): string
    {
        return $this->approvalReferences[1];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function requiredString(array $payload, string $key, int $maxLength): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value)) {
            throw new InvalidArgumentException("O campo {$key} é obrigatório.");
        }

        $value = trim($value);

        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException("O campo {$key} é inválido.");
        }

        return $value;
    }

    /**
     * @return list<int>
     */
    private static function approvedUserIds(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            throw new InvalidArgumentException('approved_user_ids deve conter pelo menos um ID explícito.');
        }

        $ids = [];

        foreach ($value as $id) {
            if (! is_int($id) || $id <= 0) {
                throw new InvalidArgumentException('approved_user_ids aceita apenas IDs inteiros positivos.');
            }

            $ids[] = $id;
        }

        if (count(array_unique($ids)) !== count($ids)) {
            throw new InvalidArgumentException('approved_user_ids não pode conter IDs repetidos.');
        }

        sort($ids);

        return $ids;
    }

    /**
     * @return list<string>
     */
    private static function approvalReferences(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) < 2) {
            throw new InvalidArgumentException('São necessárias pelo menos duas referências de aprovação.');
        }

        $references = [];

        foreach ($value as $reference) {
            if (! is_string($reference)) {
                throw new InvalidArgumentException('As referências de aprovação devem ser texto.');
            }

            $reference = trim($reference);

            if ($reference === '' || mb_strlen($reference) > 160) {
                throw new InvalidArgumentException('Existe uma referência de aprovação inválida.');
            }

            $references[] = $reference;
        }

        if (count(array_unique($references)) !== count($references)) {
            throw new InvalidArgumentException('As referências de aprovação devem ser distintas.');
        }

        return $references;
    }
}
