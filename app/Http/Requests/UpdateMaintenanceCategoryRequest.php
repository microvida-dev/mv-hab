<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceUrgency;
use App\Models\MaintenanceCategory;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateMaintenanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $category = $this->route(
            'maintenanceCategory',
        );

        return $actor instanceof User
            && $category instanceof MaintenanceCategory
            && $actor->can('update', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route(
            'maintenanceCategory',
        );

        $parentRules = [
            'nullable',
            'integer',
            $this->parentExistsRule($category),
        ];

        if ($category instanceof MaintenanceCategory) {
            $parentRules[] = Rule::notIn([
                $category->getKey(),
            ]);
        }

        return [
            'parent_id' => $parentRules,
            'code' => [
                'required',
                'string',
                'max:80',
                Rule::unique(
                    'maintenance_categories',
                    'code',
                )->ignore($category),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'default_urgency' => [
                'nullable',
                Rule::enum(MaintenanceUrgency::class),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    private function parentExistsRule(
        mixed $category,
    ): Exists {
        $municipalityId = $category instanceof MaintenanceCategory
                ? $category->municipality_id
                : null;

        $systemOnly = $category instanceof MaintenanceCategory
                && $category->is_system;

        return Rule::exists(
            'maintenance_categories',
            'id',
        )->where(
            function (Builder $query) use (
                $municipalityId,
                $systemOnly,
            ): void {
                $query
                    ->whereNull('deleted_at')
                    ->where(
                        function (Builder $scope) use (
                            $municipalityId,
                            $systemOnly,
                        ): void {
                            $scope->where(
                                function (Builder $system): void {
                                    $system
                                        ->where('is_system', true)
                                        ->whereNull(
                                            'municipality_id',
                                        );
                                },
                            );

                            if (
                                ! $systemOnly
                                && $municipalityId !== null
                            ) {
                                $scope->orWhere(
                                    function (
                                        Builder $municipal,
                                    ) use (
                                        $municipalityId,
                                    ): void {
                                        $municipal
                                            ->where(
                                                'is_system',
                                                false,
                                            )
                                            ->where(
                                                'municipality_id',
                                                $municipalityId,
                                            );
                                    },
                                );
                            }
                        },
                    );
            },
        );
    }
}
