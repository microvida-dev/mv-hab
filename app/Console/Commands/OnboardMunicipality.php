<?php

namespace App\Console\Commands;

use App\Enums\MunicipalAdministratorInvitationStatus;
use App\Models\User;
use App\Services\Municipalities\MunicipalityIdentityNormalizer;
use App\Services\Municipalities\MunicipalityOnboardingService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Throwable;

final class OnboardMunicipality extends Command
{
    protected $signature = 'mvhab:municipality:onboard
        {--actor-id= : ID técnico do operador global autorizado}
        {--name= : Nome oficial do Município}
        {--code= : Código municipal}
        {--tax-number= : NIF/NIPC do Município}
        {--contact-email= : Email institucional do Município}
        {--admin-name= : Nome do primeiro administrador municipal}
        {--admin-email= : Email do primeiro administrador municipal}
        {--justification= : Justificação administrativa aprovada}
        {--dry-run : Executar apenas preview read-only}
        {--confirm : Confirmar explicitamente a operação mutável}';

    protected $description = 'Cria o Município e o primeiro administrador municipal através de onboarding controlado.';

    public function __construct(
        private readonly MunicipalityIdentityNormalizer $normalizer,
        private readonly MunicipalityOnboardingService $onboarding,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $data = $this->normalizer->onboardingData([
                'actor_id' => $this->option('actor-id'),
                'name' => $this->option('name'),
                'code' => $this->option('code'),
                'tax_number' => $this->option('tax-number'),
                'contact_email' => $this->option('contact-email'),
                'admin_name' => $this->option('admin-name'),
                'admin_email' => $this->option('admin-email'),
                'justification' => $this->option('justification'),
            ]);
            $actor = User::query()->find($data->actorId);

            if (! $actor instanceof User) {
                throw new AuthorizationException('O operador global indicado não existe.');
            }

            $preview = $this->onboarding->preview($data, $actor);

            $this->line('MUNICIPALITY_ONBOARDING='.
                (((bool) $this->option('confirm') && ! (bool) $this->option('dry-run'))
                    ? 'READY'
                    : 'PREVIEW'));
            $this->line('OPERATION_ID='.$preview->operationId);
            $this->line('MUNICIPALITY_CODE='.$preview->municipalityCode);
            $this->line('ROLE_TEMPLATE='.$preview->roleTemplateKey);
            $this->line('ROLE_VERSION='.$preview->roleTemplateVersion);
            $this->line('ROLE_PERMISSION_COUNT='.$preview->permissionCount);
            $this->line('MFA_REQUIRED=true');
            $this->line('WRITE_OPERATIONS=0');
            $this->line('ENTITLEMENTS_ACTIVATED=0');
            $this->line('CONFLICTS='.count($preview->conflicts));

            foreach ($preview->conflicts as $conflict) {
                $this->line('CONFLICT='.$conflict);
            }

            if ((bool) $this->option('dry-run') || ! (bool) $this->option('confirm')) {
                return $preview->hasConflicts() ? 20 : self::SUCCESS;
            }

            if ($preview->hasConflicts() && ! $preview->idempotentReplay) {
                return 20;
            }

            $result = $this->onboarding->onboard($data, $actor);

            $this->line('MUNICIPALITY_ONBOARDING=PASS');
            $this->line('OPERATION_ID='.$result->operationId);
            $this->line('RUN_ID='.$result->runId);
            $this->line('MUNICIPALITY_ID='.$result->municipalityId);
            $this->line('ADMIN_USER_ID='.$result->adminUserId);
            $this->line('ADMIN_ROLE_ID='.$result->roleId);
            $this->line('INVITATION_ID='.$result->invitationId);
            $this->line('INVITATION_STATUS='.$result->invitationStatus);
            $this->line('MFA_REQUIRED=true');
            $this->line('IDEMPOTENT_REPLAY='.($result->idempotentReplay ? 'true' : 'false'));
            $this->line('ENTITLEMENTS_ACTIVATED=0');

            return $result->invitationStatus === MunicipalAdministratorInvitationStatus::Failed->value
                ? 40
                : self::SUCCESS;
        } catch (AuthorizationException $exception) {
            $this->components->error($exception->getMessage());

            return 11;
        } catch (DomainException $exception) {
            $this->components->error($exception->getMessage());

            return 20;
        } catch (Throwable $exception) {
            $this->components->error(
                'Falha técnica no onboarding municipal. Consulte o operation ID e os registos de auditoria.',
            );

            return 30;
        }
    }
}
