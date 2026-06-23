<?php

namespace App\Services\Inspections;

use App\Models\PropertyInspection;
use App\Models\PropertyInspectionItem;

class PropertyInspectionItemService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(PropertyInspection $inspection, array $data): PropertyInspectionItem
    {
        return $inspection->items()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyInspectionItem $item, array $data): PropertyInspectionItem
    {
        $item->update($data);

        return $item->refresh();
    }
}
