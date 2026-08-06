<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Contracts\Security\HumanVerificationVerifier;
use App\Enums\Security\HumanVerificationContext;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class TurnstileWidget extends Component
{
    public readonly bool $enabled;

    public readonly ?string $siteKey;

    public readonly string $action;

    public function __construct(
        public readonly string $context,
        HumanVerificationVerifier $verifier,
    ) {
        $resolvedContext = HumanVerificationContext::from($context);
        $this->enabled = $verifier->enabled($resolvedContext);
        $this->siteKey = $verifier->siteKey($resolvedContext);
        $this->action = $verifier->action($resolvedContext);
    }

    public function render(): View
    {
        return view('components.turnstile-widget');
    }
}
