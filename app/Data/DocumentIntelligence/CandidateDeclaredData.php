<?php

namespace App\Data\DocumentIntelligence;

final readonly class CandidateDeclaredData
{
    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $household
     * @param  array<string, mixed>  $income
     * @param  array<string, mixed>  $housing
     */
    public function __construct(
        public int $applicationId,
        public array $identity,
        public array $household,
        public array $income,
        public array $housing,
    ) {}

    public function value(string $path): mixed
    {
        $segments = explode('.', $path);
        $value = [
            'identity' => $this->identity,
            'household' => $this->household,
            'income' => $this->income,
            'housing' => $this->housing,
        ];

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
