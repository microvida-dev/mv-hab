<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceUrgency;
use App\Models\MaintenanceCategory;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreMaintenanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && $actor->can(
                'create',
                MaintenanceCategory::class,
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                $this->parentExistsRule(
                    $this->user()?->municipality_id,
                ),
            ],
            'code' => [
                'required',
                'string',
                'max:80',
                'unique:maintenance_categories,code',
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
        int|string|null $municipalityId,
    ): Exists {
        return Rule::exists(
            'maintenance_categories',
            'id',
        )->where(
            function (Builder $query) use (
                $municipalityId,
            ): void {
                $query
                    ->whereNull('deleted_at')
                    ->where(
                        function (Builder $scope) use (
                            $municipalityId,
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

                            if ($municipalityId !== null) {
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
