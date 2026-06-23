<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LotteryDrawType: string
{
    use HasOptions;

    case General = 'general';
    case ByHousingUnit = 'by_housing_unit';
    case ByTypology = 'by_typology';
    case ByPriorityGroup = 'by_priority_group';
    case TieBreaker = 'tie_breaker';
    case ReserveList = 'reserve_list';
    case AllocationOrder = 'allocation_order';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Geral',
            self::ByHousingUnit => 'Por habitação',
            self::ByTypology => 'Por tipologia',
            self::ByPriorityGroup => 'Por grupo prioritário',
            self::TieBreaker => 'Desempate',
            self::ReserveList => 'Lista de suplentes',
            self::AllocationOrder => 'Ordem de atribuição',
        };
    }
}
