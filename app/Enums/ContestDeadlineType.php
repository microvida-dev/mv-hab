<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestDeadlineType: string
{
    use HasOptions;

    case Applications = 'applications';
    case Review = 'review';
    case Corrections = 'corrections';
    case Revalidation = 'revalidation';
    case Complaints = 'complaints';
    case Hearing = 'hearing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Applications => 'Candidaturas',
            self::Review => 'Análise inicial',
            self::Corrections => 'Aperfeiçoamento',
            self::Revalidation => 'Revalidação',
            self::Complaints => 'Reclamações',
            self::Hearing => 'Audiência de interessados',
            self::Other => 'Outro prazo',
        };
    }

    public function isApplicationProcessingPhase(): bool
    {
        return in_array($this, [
            self::Applications,
            self::Review,
            self::Corrections,
            self::Revalidation,
        ], true);
    }

    public function processingOrder(): ?int
    {
        return match ($this) {
            self::Applications => 10,
            self::Review => 20,
            self::Corrections => 30,
            self::Revalidation => 40,
            default => null,
        };
    }

    public function defaultLabel(): string
    {
        return match ($this) {
            self::Applications => 'Submissão de candidaturas',
            self::Review => 'Análise inicial das candidaturas',
            self::Corrections => 'Aperfeiçoamento das candidaturas',
            self::Revalidation => 'Revalidação após aperfeiçoamento',
            self::Complaints => 'Reclamações',
            self::Hearing => 'Audiência de interessados',
            self::Other => 'Outro prazo',
        };
    }
}
