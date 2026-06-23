<?php

namespace App\Services\Convocations;

use App\Models\DrawConvocation;
use App\Models\LotteryDraw;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AutomaticConvocationService
{
    public function __construct(private readonly DrawConvocationService $convocations) {}

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, DrawConvocation>
     */
    public function forDraw(LotteryDraw $draw, array $data, User $actor): Collection
    {
        return $this->convocations->generate($draw, $data, $actor);
    }
}
