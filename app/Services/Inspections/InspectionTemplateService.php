<?php

namespace App\Services\Inspections;

use App\Models\InspectionChecklistTemplate;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class InspectionTemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(
        User $actor,
        array $data,
    ): InspectionChecklistTemplate {
        $municipalityId = $actor->municipality_id;

        if ($municipalityId === null) {
            throw new AuthorizationException(
                'A criação de templates exige um Município.',
            );
        }

        return DB::transaction(function () use (
            $actor,
            $data,
            $municipalityId,
        ): InspectionChecklistTemplate {
            $template = new InspectionChecklistTemplate([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'inspection_type' => $data['inspection_type'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'version_number' => $data['version_number'] ?? 1,
                'created_by' => $actor->id,
            ]);

            $template->forceFill([
                'municipality_id' => $municipalityId,
                'is_system' => false,
            ])->save();

            foreach (($data['items'] ?? []) as $index => $item) {
                if (empty($item['label'])) {
                    continue;
                }

                $template->items()->create([
                    'code' => $item['code'] ?? 'item-'.($index + 1),
                    'label' => $item['label'],
                    'description' => $item['description'] ?? null,
                    'area' => $item['area'] ?? null,
                    'is_required' => (bool) ($item['is_required'] ?? true),
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            }

            return $template->refresh();
        });
    }
}
