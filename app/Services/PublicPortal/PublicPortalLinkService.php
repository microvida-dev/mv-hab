<?php

namespace App\Services\PublicPortal;

use App\Models\PublicPortalLink;
use Illuminate\Support\Collection;

class PublicPortalLinkService
{
    /**
     * @return Collection<int, PublicPortalLink>
     */
    public function active(?string $category = null): Collection
    {
        return PublicPortalLink::query()
            ->active()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }
}
