<?php

namespace Tests\Unit\Security;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Http\Middleware\RequestCorrelationId;
use App\Services\Security\AuthorizationDenialAuditService;
use App\Services\Security\AuthorizationFailureResponder;
use App\Services\Security\AuthorizedLandingPageResolver;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class AuthorizationFailureResponderTest extends TestCase
{
    public function test_generic_json_denial_does_not_expose_the_exception_message(): void
    {
        $responder = $this->responder();
        $request = $this->jsonRequest();

        $response = $responder->respond(
            $request,
            new AccessDeniedHttpException('permission:applications.approve'),
        );
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Não tem permissão para realizar esta ação.', $payload['message']);
        $this->assertSame('access_denied', $payload['code']);
        $this->assertSame('unit-request-id', $payload['request_id']);
        $this->assertStringNotContainsString('applications.approve', (string) $response->getContent());
    }

    public function test_typed_json_denial_uses_the_whitelisted_public_message(): void
    {
        $responder = $this->responder();

        $response = $responder->respond(
            $this->jsonRequest(),
            new AccessDeniedException(AccessDenialReason::FeatureUnavailable),
        );
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            'Esta funcionalidade não está disponível para o Município atual.',
            $payload['message'],
        );
    }

    public function test_audit_failure_never_changes_the_security_response(): void
    {
        $landingPages = new AuthorizationLandingPageResolverStub;
        $audit = new AuthorizationDenialAuditServiceStub(fail: true);

        $response = (new AuthorizationFailureResponder($landingPages, $audit))
            ->respond(
                $this->jsonRequest(),
                new AccessDeniedException(AccessDenialReason::RecordOutOfScope),
            );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));
        $this->assertSame(1, $audit->calls);
        $this->assertSame(0, $landingPages->calls);
    }

    private function responder(): AuthorizationFailureResponder
    {
        $landingPages = new AuthorizationLandingPageResolverStub;
        $audit = new AuthorizationDenialAuditServiceStub;

        return new AuthorizationFailureResponder($landingPages, $audit);
    }

    private function jsonRequest(): Request
    {
        $request = Request::create(
            '/api/protected',
            'GET',
            server: ['HTTP_ACCEPT' => 'application/json'],
        );
        $request->attributes->set(RequestCorrelationId::ATTRIBUTE, 'unit-request-id');

        return $request;
    }
}

class AuthorizationLandingPageResolverStub extends AuthorizedLandingPageResolver
{
    public int $calls = 0;

    /**
     * @return array{url: string, label: string}|null
     */
    public function resolve(Request $request): ?array
    {
        $this->calls++;

        return null;
    }
}

class AuthorizationDenialAuditServiceStub extends AuthorizationDenialAuditService
{
    public int $calls = 0;

    public function __construct(private readonly bool $fail = false) {}

    public function record(
        Request $request,
        AccessDenialReason $reason,
        string $requestId,
    ): void {
        $this->calls++;

        if ($this->fail) {
            throw new RuntimeException('audit unavailable');
        }
    }
}
