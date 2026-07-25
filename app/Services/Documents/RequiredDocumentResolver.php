<?php

namespace App\Services\Documents;

use App\Models\RequiredDocument;
use Illuminate\Support\Collection;

final class RequiredDocumentResolver
{
    /**
     * Resolve as regras documentais aplicáveis ao contexto,
     * aplicando a precedência:
     *
     * concurso > programa > global.
     *
     * @return Collection<int, RequiredDocument>
     */
    public function resolve(
        ?int $programId = null,
        ?int $contestId = null,
    ): Collection {
        $rules = RequiredDocument::query()
            ->with('documentType')
            ->where('is_active', true)
            ->whereHas(
                'documentType',
                fn ($query) => $query->where('is_active', true),
            )
            ->where(function ($scope) use (
                $programId,
                $contestId,
            ): void {
                $scope->where(function ($global): void {
                    $global
                        ->whereNull('program_id')
                        ->whereNull('contest_id');
                });

                if ($programId !== null) {
                    $scope->orWhere(function ($program) use (
                        $programId,
                    ): void {
                        $program
                            ->where('program_id', $programId)
                            ->whereNull('contest_id');
                    });
                }

                if ($contestId !== null) {
                    $scope->orWhere(
                        'contest_id',
                        $contestId,
                    );
                }
            })
            ->get();

        return $rules
            ->sort(function (
                RequiredDocument $left,
                RequiredDocument $right,
            ): int {
                $precedenceComparison = $this->precedence($right)
                    <=> $this->precedence($left);

                if ($precedenceComparison !== 0) {
                    return $precedenceComparison;
                }

                /*
                 * Caso existam duplicados no mesmo âmbito,
                 * a configuração mais recente prevalece.
                 */
                return $right->id <=> $left->id;
            })
            ->unique(
                fn (RequiredDocument $rule): string => $this->identity(
                    $rule,
                ),
            )
            ->sort(function (
                RequiredDocument $left,
                RequiredDocument $right,
            ): int {
                $sortOrderComparison = $left->sort_order
                    <=> $right->sort_order;

                if ($sortOrderComparison !== 0) {
                    return $sortOrderComparison;
                }

                return $left->id <=> $right->id;
            })
            ->values();
    }

    private function precedence(
        RequiredDocument $rule,
    ): int {
        if ($rule->contest_id !== null) {
            return 300;
        }

        if ($rule->program_id !== null) {
            return 200;
        }

        return 100;
    }

    private function identity(
        RequiredDocument $rule,
    ): string {
        return json_encode([
            'document_type_id' => $rule->document_type_id,
            'required_for' => $rule->required_for->value,
            'condition_key' => $rule->condition_key,
            'condition_operator' => $rule->condition_operator->value,
            'condition_value' => $rule->condition_value,
        ], JSON_THROW_ON_ERROR);
    }
}
