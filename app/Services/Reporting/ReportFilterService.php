<?php

namespace App\Services\Reporting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ReportFilterService
{
    private const ALLOWED = ['date_from', 'date_to', 'program_id', 'contest_id', 'status', 'location'];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|string>
     */
    public function normalize(array $filters): array
    {
        $normalized = array_filter(
            Arr::only($filters, self::ALLOWED),
            static fn ($value) => $value !== null && $value !== '',
        );

        foreach (['date_from', 'date_to'] as $date) {
            if (isset($normalized[$date])) {
                $normalized[$date] = Carbon::parse($normalized[$date])->toDateString();
            }
        }

        foreach (['program_id', 'contest_id'] as $id) {
            if (isset($normalized[$id])) {
                $normalized[$id] = (int) $normalized[$id];
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hash(array $filters): string
    {
        return hash('sha256', json_encode($this->normalize($filters), JSON_THROW_ON_ERROR));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function applyDates(Builder $query, array $filters, string $column): Builder
    {
        return $query
            ->when($filters['date_from'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate($column, '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $builder, string $date) => $builder->whereDate($column, '<=', $date));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function applyApplication(Builder $query, array $filters, string $dateColumn = 'created_at'): Builder
    {
        $table = $query->getModel()->getTable();
        $dateColumn = str_contains($dateColumn, '.') ? $dateColumn : $table.'.'.$dateColumn;

        return $this->applyDates($query, $filters, $dateColumn)
            ->when($filters['program_id'] ?? null, fn (Builder $builder, int $id) => $builder->where($table.'.program_id', $id))
            ->when($filters['contest_id'] ?? null, fn (Builder $builder, int $id) => $builder->where($table.'.contest_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where($table.'.status', $status));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function applyThroughApplication(Builder $query, array $filters, string $dateColumn = 'created_at'): Builder
    {
        return $this->applyDates($query, $filters, $dateColumn)
            ->when($filters['program_id'] ?? null, fn (Builder $builder, int $id) => $builder->whereHas('application', fn (Builder $application) => $application->where('program_id', $id)))
            ->when($filters['contest_id'] ?? null, fn (Builder $builder, int $id) => $builder->whereHas('application', fn (Builder $application) => $application->where('contest_id', $id)))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function applyThroughContract(Builder $query, array $filters, string $dateColumn = 'created_at'): Builder
    {
        return $this->applyDates($query, $filters, $dateColumn)
            ->when($filters['program_id'] ?? null, fn (Builder $builder, int $id) => $builder->whereHas('leaseContract', fn (Builder $contract) => $contract->where('program_id', $id)))
            ->when($filters['contest_id'] ?? null, fn (Builder $builder, int $id) => $builder->whereHas('leaseContract', fn (Builder $contract) => $contract->where('contest_id', $id)))
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status));
    }
}
