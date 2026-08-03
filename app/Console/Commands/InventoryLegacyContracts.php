<?php

namespace App\Console\Commands;

use App\Services\Regulatory\LegacyContractInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

class InventoryLegacyContracts extends Command
{
    protected $signature = 'regulatory:inventory-legacy-contracts
        {--format=table : Formato de saída: table ou json}
        {--output= : Ficheiro de saída opcional}';

    protected $description = 'Inventaria contratos legacy sem mutações e sem dados pessoais.';

    public function __construct(
        private readonly LegacyContractInventoryService $inventory,
    ) {
        parent::__construct();
    }

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $format = strtolower((string) $this->option('format'));

        if (! in_array($format, ['table', 'json'], true)) {
            throw new InvalidArgumentException('O formato deve ser table ou json.');
        }

        $contracts = $this->inventory->inventory();
        $payload = [
            'schema_version' => 1,
            'summary' => [
                'contracts' => $contracts->count(),
                'by_classification' => $contracts
                    ->countBy('classification')
                    ->sortKeys()
                    ->all(),
            ],
            'contracts' => $contracts->all(),
        ];
        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;

        if ($output = $this->outputPath()) {
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $json);
            $this->info("Inventário escrito em: {$output}");

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $this->line($json);

            return self::SUCCESS;
        }

        $this->table(
            ['Contrato', 'Cálculo', 'Classificação', 'Razões'],
            $contracts->map(fn (array $row): array => [
                $row['contract_id'],
                $row['rent_calculation_id'] ?? '—',
                $row['classification'],
                implode(', ', $row['reasons']),
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function outputPath(): ?string
    {
        $value = $this->option('output');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return str_starts_with($value, DIRECTORY_SEPARATOR)
            ? $value
            : base_path($value);
    }
}
