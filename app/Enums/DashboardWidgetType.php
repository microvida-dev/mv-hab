<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DashboardWidgetType: string
{
    use HasOptions;

    case MetricCard = 'metric_card';
    case Table = 'table';
    case BarChart = 'bar_chart';
    case LineChart = 'line_chart';
    case PieChart = 'pie_chart';
    case StatusList = 'status_list';
    case AlertList = 'alert_list';

    public function label(): string
    {
        return match ($this) {
            self::MetricCard => 'Métrica',
            self::Table => 'Tabela',
            self::BarChart => 'Gráfico de barras',
            self::LineChart => 'Gráfico de linhas',
            self::PieChart => 'Gráfico circular',
            self::StatusList => 'Lista por estado',
            self::AlertList => 'Lista de alertas',
        };
    }
}
