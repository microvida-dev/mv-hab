<?php

declare(strict_types=1);

namespace App\Enums\Security;

enum HumanVerificationFailureReason: string
{
    case ConfigurationIncomplete = 'configuration_incomplete';
    case TokenMissing = 'token_missing';
    case TokenInvalid = 'token_invalid';
    case TransportFailure = 'transport_failure';
    case HttpFailure = 'http_failure';
    case ProviderRejected = 'provider_rejected';
    case HostnameMismatch = 'hostname_mismatch';
    case ActionMismatch = 'action_mismatch';
}
