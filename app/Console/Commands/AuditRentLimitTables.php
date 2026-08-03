<?php

namespace App\Console\Commands;

use App\Enums\AffordableRentLegalRegime;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentRuleSet;
use App\Services\Regulatory\RentLimits\RentLimitTableAuditService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

class AuditRentLimitTables extends Command
{
    protected $signature = 'regulatory:audit-rent-limit-tables
        {--regime= : Regime regulamentar a auditar}
        {--reference-date= : Data de referência YYYY-MM-DD}
        {--format=table : Formato de saída: table ou json}
        {--output= : Ficheiro de saída opcional}';

    protected $description = 'Audita, sem mutações, a proveniência e cobertura das tabelas de limites de renda.';

    public function __construct(
        private readonly RentLimitTableAuditService $audit,
    ) {
        parent::__construct();
    }

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $regime = $this->regime();
        $format = strtolower((string) $this->option('format'));

        if (! in_array($format, ['table', 'json'], true)) {
            throw new InvalidArgumentException('O formato deve ser table ou json.');
        }

        $referenceDate = $this->referenceDate();
        $tables = [];
        $profiles = AffordableRentRegulatoryProfile::query()
            ->select([
                'id',
                'municipality_id',
                'legal_regime',
                'code',
                'version',
                'source_version',
                'rent_limits_configured',
            ])
            ->where('legal_regime', $regime->value)
            ->orderBy('code')
            ->orderBy('version')
            ->get();

        foreach ($profiles as $profile) {
            $ruleSets = RentRuleSet::query()
                ->select([
                    'id',
                    'regulatory_profile_id',
                    'program_id',
                    'contest_id',
                    'effort_rate_percentage',
                    'minimum_rent',
                    'maximum_rent',
                ])
                ->where('regulatory_profile_id', $profile->id)
                ->orderBy('program_id')
                ->orderBy('contest_id')
                ->orderBy('id')
                ->get();

            if ($ruleSets->isEmpty()) {
                $tables[] = [
                    'profile_id' => $profile->id,
                    'profile_code' => $profile->code,
                    'profile_version' => $profile->version,
                    ...$this->audit->audit($profile, null, $referenceDate)->toArray(),
                ];

                continue;
            }

            foreach ($ruleSets as $ruleSet) {
                $tables[] = [
                    'profile_id' => $profile->id,
                    'profile_code' => $profile->code,
                    'profile_version' => $profile->version,
                    ...$this->audit->audit($profile, $ruleSet, $referenceDate)->toArray(),
                ];
            }
        }

        $tableCollection = collect($tables);
        $findings = $profiles->isEmpty()
            ? ['Não existe qualquer perfil regulamentar instalado para o regime solicitado.']
            : [];
        $status = match (true) {
            $profiles->isEmpty() || $tables === [] => 'configuration_incomplete',
            $tableCollection->contains('status', 'requires_manual_review') => 'requires_manual_review',
            $tableCollection->every(fn (array $table): bool => $table['status'] === 'configured') => 'configured',
            default => 'configuration_incomplete',
        };
        $payload = [
            'schema_version' => 1,
            'legal_regime' => $regime->value,
            'reference_date' => $referenceDate->toDateString(),
            'status' => $status,
            'findings' => $findings,
            'summary' => [
                'profiles' => $profiles->count(),
                'tables' => count($tables),
                'configured' => $tableCollection->where('status', 'configured')->count(),
                'incomplete' => $tableCollection->where('status', 'incomplete')->count(),
                'requires_manual_review' => $tableCollection
                    ->where('status', 'requires_manual_review')
                    ->count(),
            ],
            'tables' => $tables,
        ];
        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;

        if ($output = $this->outputPath()) {
            File::ensureDirectoryExists(dirname($output));
            File::put($output, $json);
            $this->info("Auditoria escrita em: {$output}");

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $this->line($json);

            return self::SUCCESS;
        }

        $this->table(
            ['Perfil', 'Versão', 'Rule set', 'Estado', 'Linhas', 'Demo'],
            collect($tables)->map(fn (array $row): array => [
                $row['profile_code'],
                $row['profile_version'],
                $row['rent_rule_set_id'] ?? '—',
                $row['status'],
                $row['actual_row_count'],
                $row['demo_only'] ? 'sim' : 'não',
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function regime(): AffordableRentLegalRegime
    {
        $value = $this->option('regime');

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('A opção --regime é obrigatória.');
        }

        $regime = AffordableRentLegalRegime::tryFrom(trim($value));

        if (! $regime instanceof AffordableRentLegalRegime) {
            throw new InvalidArgumentException('O regime regulamentar indicado não é válido.');
        }

        return $regime;
    }

    private function referenceDate(): CarbonImmutable
    {
        $value = $this->option('reference-date');

        return is_string($value) && trim($value) !== ''
            ? CarbonImmutable::parse(trim($value), 'Europe/Lisbon')
            : CarbonImmutable::today('Europe/Lisbon');
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
