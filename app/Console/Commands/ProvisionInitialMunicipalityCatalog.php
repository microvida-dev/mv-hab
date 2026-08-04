<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Municipalities\AlcanenaInitialCatalogService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Throwable;

final class ProvisionInitialMunicipalityCatalog extends Command
{
    protected $signature = 'mvhab:municipality:provision-initial-catalog
        {--actor-id= : ID técnico do ator autorizado}
        {--municipality=ALCANENA : Código do Município}
        {--profile=alcanena-2026 : Perfil versionado do catálogo}
        {--dry-run : Executar apenas preview read-only}
        {--confirm : Confirmar explicitamente a operação mutável}';

    protected $description = 'Cria o Programa e o Concurso iniciais de Alcanena em estado de rascunho.';

    public function __construct(
        private readonly AlcanenaInitialCatalogService $catalog,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $actorId = filter_var($this->option('actor-id'), FILTER_VALIDATE_INT);

            if (! is_int($actorId) || $actorId <= 0) {
                throw new DomainException('A opção --actor-id é obrigatória.');
            }

            $actor = User::query()->find($actorId);

            if (! $actor instanceof User) {
                throw new AuthorizationException('O ator indicado não existe.');
            }

            $municipality = (string) $this->option('municipality');
            $profile = (string) $this->option('profile');
            $preview = $this->catalog->preview($actor, $municipality, $profile);

            $this->line('INITIAL_CATALOG='.
                (((bool) $this->option('confirm') && ! (bool) $this->option('dry-run'))
                    ? 'READY'
                    : 'PREVIEW'));
            $this->line('MUNICIPALITY_CODE='.$preview->municipalityCode);
            $this->line('PROFILE='.$preview->profile);
            $this->line('FINGERPRINT='.$preview->fingerprint);
            $this->line('PROGRAM_SLUG='.$preview->programSlug);
            $this->line('CONTEST_CODE='.$preview->contestCode);
            $this->line('PROGRAM_STATUS=draft');
            $this->line('CONTEST_STATUS=draft');
            $this->line('PUBLICATION_BLOCKED=true');
            $this->line('WRITE_OPERATIONS=0');
            $this->line('ENTITLEMENTS_ACTIVATED=0');
            $this->line('CONFLICTS='.count($preview->conflicts));

            foreach ($preview->conflicts as $conflict) {
                $this->line('CONFLICT='.$conflict);
            }

            if ((bool) $this->option('dry-run') || ! (bool) $this->option('confirm')) {
                return $preview->hasConflicts() ? 20 : self::SUCCESS;
            }

            if ($preview->hasConflicts()) {
                return 20;
            }

            $result = $this->catalog->provision($actor, $municipality, $profile);

            $this->line('INITIAL_CATALOG=PASS');
            $this->line('MUNICIPALITY_ID='.$result->municipalityId);
            $this->line('PROGRAM_ID='.$result->programId);
            $this->line('CONTEST_ID='.$result->contestId);
            $this->line('PROGRAM_STATUS='.$result->programStatus);
            $this->line('CONTEST_STATUS='.$result->contestStatus);
            $this->line('PUBLICATION_BLOCKED=true');
            $this->line('IDEMPOTENT_REPLAY='.($result->idempotentReplay ? 'true' : 'false'));
            $this->line('ENTITLEMENTS_ACTIVATED=0');

            return self::SUCCESS;
        } catch (AuthorizationException $exception) {
            $this->components->error($exception->getMessage());

            return 11;
        } catch (DomainException $exception) {
            $this->components->error($exception->getMessage());

            return 20;
        } catch (Throwable $exception) {
            $this->components->error('Falha técnica no provisionamento do catálogo municipal inicial.');

            return 30;
        }
    }
}
