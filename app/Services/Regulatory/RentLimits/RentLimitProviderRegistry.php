<?php

namespace App\Services\Regulatory\RentLimits;

use App\Models\AffordableRentRegulatoryProfile;
use RuntimeException;

class RentLimitProviderRegistry
{
    /**
     * @param  iterable<RentLimitProviderInterface>  $providers
     */
    public function __construct(private readonly iterable $providers) {}

    public function forProfile(AffordableRentRegulatoryProfile $profile): RentLimitProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($profile)) {
                return $provider;
            }
        }

        throw new RuntimeException('Não existe provider de limites de renda para o regime indicado.');
    }
}
