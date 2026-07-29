<?php

namespace App\Http\Middleware;

use App\Enums\CandidateExperienceFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCandidateExperienceFeatureIsEnabled
{
    public function handle(
        Request $request,
        Closure $next,
        string $feature,
    ): Response {
        $resolved = CandidateExperienceFeature::tryFrom($feature);

        abort_if(
            ! $resolved instanceof CandidateExperienceFeature,
            404,
        );

        abort_unless(
            (bool) config(
                'mvhab.candidate_experience_runtime.'.$resolved->value,
                false,
            ),
            404,
        );

        return $next($request);
    }
}
