<?php

namespace App\Services\Security;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Http\Middleware\RequestCorrelationId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthorizationFailureResponder
{
    public function __construct(
        private readonly AuthorizedLandingPageResolver $landingPages,
        private readonly AuthorizationDenialAuditService $audit,
    ) {}

    public function respond(Request $request, Throwable $exception): Response
    {
        $reason = $this->reason($exception);
        $requestId = $this->requestId($request);

        $this->recordDenial($request, $reason, $requestId);

        if ($this->expectsJson($request)) {
            return response()
                ->json([
                    'message' => $reason->publicMessage(),
                    'code' => $reason->publicCode(),
                    'request_id' => $requestId,
                ], 403)
                ->header(RequestCorrelationId::HEADER, $requestId);
        }

        $landingPage = $this->landingPages->resolve($request);

        if (! $request->isMethodSafe()) {
            $redirectUrl = $this->safeReferer($request);

            if (is_string($redirectUrl)) {
                return redirect()
                    ->to($redirectUrl, 303)
                    ->with(
                        'warning',
                        $reason->publicMessage().' A operação não foi executada.',
                    )
                    ->withHeaders([RequestCorrelationId::HEADER => $requestId]);
            }
        }

        return response()
            ->view('errors.403', [
                'publicMessage' => $reason->publicMessage(),
                'requestId' => $requestId,
                'landingPage' => $landingPage,
            ], 403)
            ->header(RequestCorrelationId::HEADER, $requestId);
    }

    private function reason(Throwable $exception): AccessDenialReason
    {
        $current = $exception;

        do {
            if ($current instanceof AccessDeniedException) {
                return $current->reason;
            }

            $current = $current->getPrevious();
        } while ($current instanceof Throwable);

        return AccessDenialReason::MissingPermission;
    }

    private function requestId(Request $request): string
    {
        $requestId = $request->attributes->get(RequestCorrelationId::ATTRIBUTE);

        if (is_string($requestId) && $requestId !== '') {
            return $requestId;
        }

        $requestId = (string) Str::uuid();
        $request->attributes->set(RequestCorrelationId::ATTRIBUTE, $requestId);

        return $requestId;
    }

    private function expectsJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private function safeReferer(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $refererParts = parse_url($referer);
        $originParts = parse_url($request->root());

        if (! is_array($refererParts) || ! is_array($originParts)) {
            return null;
        }

        if (
            ($refererParts['scheme'] ?? null) !== ($originParts['scheme'] ?? null)
            || ($refererParts['host'] ?? null) !== ($originParts['host'] ?? null)
            || $this->port($refererParts) !== $this->port($originParts)
        ) {
            return null;
        }

        $refererPath = $refererParts['path'] ?? '/';

        if ($this->samePath($request, $refererPath)) {
            return null;
        }

        $query = isset($refererParts['query'])
            ? '?'.$refererParts['query']
            : '';

        return $request->root().$refererPath.$query;
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private function port(array $parts): int
    {
        if (isset($parts['port']) && is_int($parts['port'])) {
            return $parts['port'];
        }

        return ($parts['scheme'] ?? null) === 'https' ? 443 : 80;
    }

    private function samePath(Request $request, string $path): bool
    {
        return rtrim($path, '/') === rtrim('/'.$request->path(), '/');
    }

    private function recordDenial(
        Request $request,
        AccessDenialReason $reason,
        string $requestId,
    ): void {
        try {
            $this->audit->record($request, $reason, $requestId);
        } catch (Throwable $exception) {
            try {
                report($exception);
            } catch (Throwable) {
                // A auditoria nunca pode transformar uma recusa num erro 500.
            }
        }
    }
}
