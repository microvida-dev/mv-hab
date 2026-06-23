<?php

namespace App\Services\Inspections;

use App\Models\InspectionChecklistTemplate;
use App\Models\User;

class InspectionTemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(User $actor, array $data): InspectionChecklistTemplate
    {
        $template = InspectionChecklistTemplate::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'inspection_type' => $data['inspection_type'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'version_number' => $data['version_number'] ?? 1,
            'created_by' => $actor->id,
        ]);

        foreach (($data['items'] ?? []) as $index => $item) {
            if (! empty($item['label'])) {
                $template->items()->create([
                    'code' => $item['code'] ?? 'item-'.($index + 1),
                    'label' => $item['label'],
                    'description' => $item['description'] ?? null,
                    'area' => $item['area'] ?? null,
                    'is_required' => (bool) ($item['is_required'] ?? true),
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            }
        }

        return $template->refresh();
    }
}
