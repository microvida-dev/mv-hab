<?php

namespace App\Http\Requests;

use App\Models\MaintenanceSupplier;
use App\Models\User;

class UpdateMaintenanceSupplierRequest extends StoreMaintenanceSupplierRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $supplier = $this->route('maintenanceSupplier');

        return $actor instanceof User
            && $supplier instanceof MaintenanceSupplier
            && $actor->can('update', $supplier);
    }
}
