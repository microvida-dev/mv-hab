<?php

namespace App\Services\Cases;

use App\Models\Application;
use Illuminate\Database\Eloquent\Model;

class CaseWorkspaceResolver
{
    public function __construct(private readonly CaseTypeRegistry $registry) {}

    /**
     * @return array<string, array{label: string, model: class-string<Model>|null, implemented: bool}>
     */
    public function supportedTypes(): array
    {
        return collect($this->registry->types())
            ->map(fn (array $type): array => [
                'label' => $type['label'],
                'model' => $type['model'],
                'implemented' => true,
            ])
            ->all();
    }

    public function typeFor(Model $case): string
    {
        return $case instanceof Application ? 'application' : ($this->registry->typeFor($case) ?? 'unknown');
    }
}
