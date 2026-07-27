<?php

namespace App\Console\Commands;

use App\Enums\HousingCompatibilityStatus;
use App\Models\ApplicationPreference;
use App\Models\ContestHousingUnit;
use App\Models\HousingPreference;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileLegacyHousingPreferences extends Command
{
    protected $signature = 'preferences:reconcile-legacy
        {--apply : Aplicar apenas correspondências inequívocas}';

    protected $description = 'Reconcilia preferências legacy sem inventar correspondências ambíguas.';

    public function __construct(private readonly AuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $summary = [
            'eligible' => 0,
            'applied' => 0,
            'already_reconciled' => 0,
            'ambiguous' => 0,
            'conflict' => 0,
        ];

        ApplicationPreference::query()
            ->select([
                'id',
                'application_id',
                'housing_unit_id',
                'preference_order',
                'notes',
            ])
            ->with('application:id,user_id,program_id,contest_id,submitted_at')
            ->orderBy('id')
            ->chunkById(200, function ($preferences) use ($apply, &$summary): void {
                foreach ($preferences as $legacy) {
                    $application = $legacy->application;

                    if ($application === null) {
                        $summary['ambiguous']++;

                        continue;
                    }

                    $matches = ContestHousingUnit::query()
                        ->select(['id', 'housing_unit_id'])
                        ->where('contest_id', $application->contest_id)
                        ->where('housing_unit_id', $legacy->housing_unit_id)
                        ->limit(2)
                        ->get();

                    if ($matches->count() !== 1) {
                        $summary['ambiguous']++;

                        continue;
                    }

                    $unit = $matches->first();
                    $existing = HousingPreference::withTrashed()
                        ->where(function ($query) use ($legacy): void {
                            $query->where(
                                'legacy_application_preference_id',
                                $legacy->id,
                            )->orWhere(function ($candidate) use ($legacy): void {
                                $candidate
                                    ->where(
                                        'application_id',
                                        $legacy->application_id,
                                    )
                                    ->where(
                                        'housing_unit_id',
                                        $legacy->housing_unit_id,
                                    );
                            });
                        })
                        ->first();

                    if ($existing instanceof HousingPreference) {
                        if (
                            $existing->application_id === $legacy->application_id
                            && $existing->housing_unit_id === $legacy->housing_unit_id
                            && $existing->preference_order === $legacy->preference_order
                        ) {
                            $summary['already_reconciled']++;
                        } else {
                            $summary['conflict']++;
                        }

                        continue;
                    }

                    $summary['eligible']++;

                    if (! $apply) {
                        continue;
                    }

                    DB::transaction(function () use (
                        $legacy,
                        $application,
                        $unit,
                        &$summary,
                    ): void {
                        $preference = new HousingPreference([
                            'preference_order' => $legacy->preference_order,
                            'notes' => $legacy->notes,
                            'compatibility_status' => HousingCompatibilityStatus::RequiresRevalidation,
                            'compatibility_snapshot' => null,
                            'evaluated_at' => null,
                            'invalidated_at' => now(),
                            'invalidation_reason' => 'Preferência importada do sistema legacy; requer validação.',
                        ]);
                        $preference->forceFill([
                            'application_id' => $application->id,
                            'user_id' => $application->user_id,
                            'contest_id' => $application->contest_id,
                            'contest_housing_unit_id' => $unit?->id,
                            'housing_unit_id' => $legacy->housing_unit_id,
                            'regulatory_snapshot_id' => null,
                            'submitted_at' => $application->submitted_at,
                            'locked_at' => null,
                            'legacy_application_preference_id' => $legacy->id,
                        ])->save();

                        $this->auditLogger->record(
                            AuditEvents::CREATE,
                            $preference,
                            'allocations',
                            'legacy_housing_preference_reconciled',
                            'Preferência habitacional legacy reconciliada para revisão.',
                            metadata: [
                                'application_id' => $application->id,
                                'legacy_preference_id' => $legacy->id,
                            ],
                        );
                        $summary['applied']++;
                    });
                }
            });

        $this->table(
            ['Estado', 'Total'],
            collect($summary)
                ->map(fn (int $count, string $status): array => [$status, $count])
                ->values()
                ->all(),
        );
        $this->info($apply
            ? 'Reconciliação aplicada apenas às correspondências inequívocas.'
            : 'Dry-run concluído. Use --apply para aplicar as correspondências inequívocas.');

        return self::SUCCESS;
    }
}
