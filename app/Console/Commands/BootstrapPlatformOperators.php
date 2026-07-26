<?php

namespace App\Console\Commands;

use App\Data\Platform\PlatformOperatorBootstrapManifest;
use App\Services\Platform\PlatformOperatorManagementService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class BootstrapPlatformOperators extends Command
{
    protected $signature = 'platform-operators:bootstrap
        {--manifest= : Caminho absoluto para o manifesto externo aprovado}
        {--dry-run : Validar e apresentar o plano sem alterar dados}
        {--confirm : Confirmar explicitamente a aplicação do manifesto}';

    protected $description = 'Inicializa operadores de plataforma a partir de IDs explicitamente aprovados.';

    public function __construct(
        private readonly PlatformOperatorManagementService $operators,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $manifest = $this->loadManifest();
            $this->assertEnvironment($manifest);
            $plan = $this->operators->planBootstrap($manifest);

            $this->table(
                ['ID do utilizador', 'Estado'],
                array_map(
                    fn (array $item): array => [$item['user_id'], $item['status']],
                    $plan,
                ),
            );

            if ((bool) $this->option('dry-run')) {
                $this->info('Dry-run concluído. Nenhuma associação foi alterada.');

                return self::SUCCESS;
            }

            if (! (bool) $this->option('confirm')) {
                $this->error('A execução mutável exige a opção --confirm.');

                return self::FAILURE;
            }

            $assignments = $this->operators->bootstrap($manifest);
            $this->info(count($assignments).' associação(ões) validada(s) com sucesso.');

            return self::SUCCESS;
        } catch (InvalidArgumentException|DomainException|JsonException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @throws JsonException
     */
    private function loadManifest(): PlatformOperatorBootstrapManifest
    {
        $path = $this->option('manifest');

        if (! is_string($path) || trim($path) === '') {
            throw new InvalidArgumentException('A opção --manifest é obrigatória.');
        }

        $path = trim($path);
        $realPath = realpath($path);

        if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
            throw new InvalidArgumentException('O manifesto indicado não existe ou não pode ser lido.');
        }

        $repositoryPath = realpath(base_path());

        if (is_string($repositoryPath)
            && ($realPath === $repositoryPath
                || str_starts_with($realPath, $repositoryPath.DIRECTORY_SEPARATOR))) {
            throw new InvalidArgumentException('O manifesto deve permanecer fora do repositório.');
        }

        $contents = File::get($realPath);
        $payload = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);

        if (! is_array($payload) || array_is_list($payload)) {
            throw new InvalidArgumentException('O manifesto deve conter um objeto JSON.');
        }

        /** @var array<string, mixed> $payload */
        return PlatformOperatorBootstrapManifest::fromArray($payload);
    }

    private function assertEnvironment(PlatformOperatorBootstrapManifest $manifest): void
    {
        if ($manifest->environment !== app()->environment()) {
            throw new InvalidArgumentException(
                "O ambiente do manifesto ({$manifest->environment}) não corresponde ao ambiente atual.",
            );
        }
    }
}
