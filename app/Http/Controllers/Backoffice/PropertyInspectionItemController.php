<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyInspectionItemRequest;
use App\Http\Requests\UpdatePropertyInspectionItemRequest;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionItem;
use App\Services\Inspections\PropertyInspectionItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PropertyInspectionItemController extends Controller
{
    public function __construct(private readonly PropertyInspectionItemService $items) {}

    public function store(StorePropertyInspectionItemRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('create', PropertyInspectionItem::class);
        $this->items->store($propertyInspection, $request->validated());

        return back()->with('success', 'Item criado.');
    }

    public function update(UpdatePropertyInspectionItemRequest $request, PropertyInspectionItem $propertyInspectionItem): RedirectResponse
    {
        Gate::authorize('update', $propertyInspectionItem);
        $this->items->update($propertyInspectionItem, $request->validated());

        return back()->with('success', 'Item atualizado.');
    }
}
